import requests

class LaTeXManager:
    @staticmethod
    def generate_pdf(latex_code: str):
        """
        Sends LaTeX code to TexLive.net and returns the PDF bytes.
        """
        url = "https://texlive.net/cgi-bin/latexcgi"
        
        # TexLive.net works best with multipart/form-data (using 'files' in requests)
        # and requires \r\n line endings for some engines.
        safe_latex = latex_code.replace('\n', '\r\n')
        
        payload = {
            "filecontents[]": (None, safe_latex),
            "filename[]": (None, "document.tex"),
            "engine": (None, "pdflatex"),
            "return": (None, "pdf")
        }
        
        try:
            # Using files= sends it as multipart/form-data
            response = requests.post(url, files=payload, timeout=60)
            
            if response.status_code == 200:
                # CRITICAL: Check if it's actually a PDF
                if response.content.startswith(b"%PDF-"):
                    return response.content
                else:
                    # It's likely a LaTeX log file or error message
                    error_log = response.text
                    print(f"LaTeX Compilation Error Log (Tail):\n{error_log[-2000:]}")
                    # Find last occurrence of '!' for the actual error
                    last_error = error_log.rfind('!')
                    snippet = error_log[last_error:last_error+500] if last_error != -1 else error_log[-500:]
                    raise Exception(f"LaTeX compilation failed: {snippet}")
            else:
                error_msg = response.text or "Unknown error"
                print(f"TeXLive Online Error ({response.status_code}): {error_msg}")
                raise Exception(f"LaTeX API Error: {error_msg}")
        except Exception as e:
            print(f"Request Error: {e}")
            raise e
