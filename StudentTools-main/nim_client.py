import os
import requests
import json
import time
from dotenv import load_dotenv

load_dotenv()

NVIDIA_API_KEY = os.getenv("NVIDIA_API_KEY")
API_URL = "https://integrate.api.nvidia.com/v1/chat/completions"

FALLBACK_MAP = {
    "google/gemma-4-31b-it": [
        "google/gemma-3-12b-it",
        "nvidia/llama-3.3-nemotron-super-49b-v1.5",
        "meta/llama-3.3-70b-instruct"
    ],
    "mistralai/mistral-large-3-675b-instruct-2512": [
        "meta/llama-3.3-70b-instruct",
        "nvidia/llama-3.3-nemotron-super-49b-v1.5"
    ],
    "moonshotai/kimi-k2.6": [
        "mistralai/mistral-large-3-675b-instruct-2512",
        "meta/llama-3.3-70b-instruct",
        "nvidia/llama-3.3-nemotron-super-49b-v1.5"
    ],
    "deepseek-ai/deepseek-v4-flash": [
        "meta/llama-3.3-70b-instruct",
        "nvidia/llama-3.3-nemotron-super-49b-v1.5"
    ]
}

class NIMClient:
    def __init__(self, model="meta/llama-3.1-405b-instruct"):
        self.model = model
        self.api_key = NVIDIA_API_KEY

    def generate_chat(self, messages, temperature=0.6, max_tokens=1024, fallback_allowed=True, timeout_override=None, response_format=None):
        """
        Generate a chat completion via NVIDIA NIM API.
        
        timeout_override: optional int (seconds) for the read timeout.
                          Use this for heavy generation tasks (e.g. 8192 tokens of code).
                          If None, auto-calculated from max_tokens.
        """
        if not self.api_key:
            raise ValueError("NVIDIA_API_KEY not found in environment")

        headers = {
            "Authorization": f"Bearer {self.api_key}",
            "Content-Type": "application/json"
        }

        # Build fallback list starting with target model
        models_to_try = [self.model]
        if fallback_allowed and self.model in FALLBACK_MAP:
            models_to_try.extend(FALLBACK_MAP[self.model])

        last_error = None

        for model in models_to_try:
            # Primary model gets 2 attempts, fallback models get 1 attempt
            max_attempts = 2 if model == self.model else 1
            for attempt in range(max_attempts):
                print(f"[NIMClient] Attempt {attempt + 1} with model: {model}...")
                try:
                    payload = {
                        "model": model,
                        "messages": messages,
                        "temperature": temperature,
                        "max_tokens": max_tokens,
                        "top_p": 0.7,
                        "stream": False
                    }
                    if response_format:
                        payload["response_format"] = response_format

                    # Smart split timeout: (connect_timeout, read_timeout)
                    # - connect_timeout = 10s → fails FAST if server is unreachable
                    # - read_timeout = scaled by task size, or caller override
                    connect_timeout = 10
                    if timeout_override:
                        read_timeout = timeout_override
                    elif "flash" in model.lower():
                        read_timeout = 30
                    elif max_tokens >= 4096:
                        read_timeout = 120  # Heavy generation (PPTX code, long LaTeX)
                    else:
                        read_timeout = 60   # Standard tasks (classification, diagrams)
                    
                    current_timeout = (connect_timeout, read_timeout)
                    print(f"[NIMClient] Timeout: connect={connect_timeout}s, read={read_timeout}s (max_tokens={max_tokens})")
                    response = requests.post(API_URL, headers=headers, json=payload, timeout=current_timeout)

                    if response.status_code != 200:
                        err_text = response.text
                        print(f"[NIMClient] Error from API (Status {response.status_code}): {err_text}")
                        last_error = Exception(f"NIM API Error: {response.status_code} - {err_text}")
                        # Don't retry the attempt if it's a 4xx error (validation/auth), but retry on 5xx or timeouts
                        if 400 <= response.status_code < 500:
                            break
                        time.sleep(2)
                        continue

                    data = response.json()
                    # Validate that response contains choices
                    if 'choices' not in data or not data['choices']:
                        raise Exception(f"Invalid API response structure: {data}")

                    print(f"[NIMClient] Success with model {model}!")
                    return data

                except requests.exceptions.ConnectTimeout as e:
                    print(f"[NIMClient] Server unreachable for {model} (connect timeout {connect_timeout}s): {e}")
                    last_error = e
                    # Server is down — skip retries for this model, move to next
                    break
                except requests.exceptions.ReadTimeout as e:
                    print(f"[NIMClient] Model {model} too slow (read timeout {read_timeout}s): {e}")
                    last_error = e
                    time.sleep(1)
                except requests.exceptions.Timeout as e:
                    print(f"[NIMClient] Timeout on model {model}: {e}")
                    last_error = e
                    time.sleep(1)
                except Exception as e:
                    print(f"[NIMClient] Exception during generation on model {model}: {e}")
                    last_error = e
                    time.sleep(2)

        # If we exhausted all models and attempts
        raise last_error if last_error else Exception("Failed to generate response from all models.")


    def generate_structured_json(self, system_prompt, user_prompt, temperature=0.2):
        """Helper to get clean JSON response from NIM"""
        messages = [
            {"role": "system", "content": f"{system_prompt}\nOutput RAW JSON ONLY."},
            {"role": "user", "content": user_prompt}
        ]
        
        response_data = self.generate_chat(messages, temperature=temperature, max_tokens=4096)
        content = response_data['choices'][0]['message']['content']
        
        # Clean up possible markdown fences
        content = content.replace("```json", "").replace("```", "").strip()
        
        # Find first { and last } to be safe
        first_brace = content.find("{")
        last_brace = content.rfind("}")
        if first_brace != -1 and last_brace != -1:
            content = content[first_brace:last_brace+1]
            
        try:
            return json.loads(content)
        except json.JSONDecodeError as e:
            print(f"Failed to parse JSON: {content}")
            raise e
