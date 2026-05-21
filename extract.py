import os, json, re

keys = set()
for root, _, files in os.walk('resources/views'):
    for f in files:
        if f.endswith('.blade.php'):
            with open(os.path.join(root, f), 'r', encoding='utf-8') as file:
                content = file.read()
                matches = re.findall(r"__\(\s*['\"](.*?)['\"]\s*\)", content)
                keys.update(matches)

with open('lang/ckb.json', 'r', encoding='utf-8') as f:
    ckb = json.load(f)

missing = sorted(list(keys - set(ckb.keys())))
with open('missing_keys.json', 'w', encoding='utf-8') as f:
    json.dump(missing, f, indent=4)
print("Missing keys:", len(missing))
