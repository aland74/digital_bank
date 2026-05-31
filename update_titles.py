import json

titles = {
    "Transfer Request Sent": "داواکاری گواستنەوە نێردرا",
    "Transfer Cancelled": "گواستنەوە هەڵوەشایەوە",
    "Transfer Expired": "گواستنەوە بەسەرچوو",
    "Welcome to Distributed Bank! 🎉": "بەخێربێیت بۆ Distributed Bank! 🎉",
    "Card Frozen — Security Alert": "کارتەکە سڕکرا — ئاگاداری ئاسایش",
    "Card Frozen ❄️": "کارتەکە سڕکرا ❄️",
    "Card PIN Changed": "پین کۆدی کارتەکە گۆڕدرا",
    "Loan Application Submitted": "داواکاری قەرز نێردرا",
    "New Loan Application": "داواکاری قەرزی نوێ",
    "New KYC Document": "بەڵگەنامەی KYC نوێ",
    "Document Rejected": "بەڵگەنامە ڕەتکرایەوە",
    "New Card Created! 💳": "کارتی نوێ دروستکرا! 💳",
    "Card Created — Pending Activation": "کارت دروستکرا — چاوەڕێی چالاککردنە",
    "Incoming Transfer Request": "داواکاری گواستنەوەی هاتوو",
    "Transfer Accepted ✅": "گواستنەوە پەسەندکرا ✅",
    "Transfer Received 💰": "گواستنەوە پێگەیشت 💰",
    "Transfer Declined": "گواستنەوە ڕەتکرایەوە",
    "Card Activated! 🎉": "کارت چالاککرا! 🎉",
    "Loan Application Unavailable": "داواکاری قەرز بەردەست نییە"
}

with open('lang/ckb.json', 'r', encoding='utf-8') as f:
    ckb = json.load(f)

for k, v in titles.items():
    ckb[k] = v

with open('lang/ckb.json', 'w', encoding='utf-8') as f:
    json.dump(ckb, f, ensure_ascii=False, indent=4)
print("Updated ckb.json with Notification titles successfully!")
