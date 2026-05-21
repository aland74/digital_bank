import json

titles = {
    "Account Verified! 🎉": "هەژمار پەسەندکرا! 🎉",
    "Account Fully Verified! 🎉": "هەژمار بە تەواوی پەسەندکرا! 🎉",
    "Loan Approved! 🎉": "قەرز پەسەندکرا! 🎉",
    "Loan Application Rejected": "داواکاری قەرز ڕەتکرایەوە",
    "Document Verified ✅": "بەڵگەنامە پەسەندکرا ✅"
}

with open('lang/ckb.json', 'r', encoding='utf-8') as f:
    ckb = json.load(f)

for k, v in titles.items():
    ckb[k] = v

with open('lang/ckb.json', 'w', encoding='utf-8') as f:
    json.dump(ckb, f, ensure_ascii=False, indent=4)
print("Updated ckb.json with new titles!")
