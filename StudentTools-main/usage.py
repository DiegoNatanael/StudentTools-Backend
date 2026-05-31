import os
from datetime import date
from fastapi import Request

usage_db = {}
total_stats = {"total_generations": 0}
ADMIN_TOKEN = os.getenv("ADMIN_TOKEN", "super_secret_bypass_99")

def is_admin(request: Request) -> bool:
    return request.headers.get("X-Admin-Token") == ADMIN_TOKEN

def get_user_id(request: Request) -> str:
    uid = request.headers.get("X-Device-Id")
    if not uid:
        forwarded = request.headers.get("X-Forwarded-For")
        uid = forwarded.split(',')[0].strip() if forwarded else request.client.host
    return uid

def is_allowed(request: Request) -> bool:
    return True

def record_usage(request: Request):
    if is_admin(request): return
    today = str(date.today())
    uid = get_user_id(request)
    if today not in usage_db: usage_db[today] = {}
    usage_db[today][uid] = usage_db[today].get(uid, 0) + 1
    total_stats["total_generations"] += 1
