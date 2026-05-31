import json

translations = {
    "256-bit AES encryption for all transactions": "کۆدکردنی جۆری 256-bit AES بۆ هەموو مامەڵەکان",
    "A beautifully designed, premium digital banking platform. Escrow transfers, secure smart cards, and intelligent loan insights at your fingertips.": "پلاتفۆرمێکی بانکی دیجیتاڵی نایاب کە بە جوانی دیزاین کراوە. گواستنەوەی پارێزراو، کارتی زیرەکی پارێزراو، و زانیاری زیرەکی قەرز لەبەردەستتدایە.",
    "About Distributed Bank": "دەربارەی Distributed Bank",
    "Access highly competitive loans backed directly by real-time bank reserve limits. Instant approvals driven by an intelligent capacity system.": "دەستت بگات بە قەرزی زۆر ڕکابەرانە کە ڕاستەوخۆ پشتگیری دەکرێت لەلایەن سنوورەکانی یەدەگی بانکی ڕاستەقینەوە. ڕەزامەندی خێرا کە لەلایەن سیستەمێکی توانای زیرەکەوە بەڕێوەدەچێت.",
    "Access your account from any city, seamlessly": "دەستت بە هەژمارەکەت بگات لە هەر شارێکەوە، بەبێ کێشە",
    "Actionable Notifications": "ئاگادارکردنەوەی کردارەکی",
    "Banking": "بانکداری",
    "Branches in Erbil, Sulaimaniyah & Duhok": "لقەکانمان لە هەولێر، سلێمانی و دهۆک",
    "Cards Management": "بەڕێوەبردنی کارتەکان",
    "Careers": "هەلی کار",
    "Company": "کۆمپانیا",
    "Compliance": "گونجان",
    "Contact Us": "پەیوەندیمان پێوە بکە",
    "Cookie Policy": "سیاسەتی کووکی",
    "Countries Supported": "وڵاتە پاڵپشتیکراوەکان",
    "Designed for": "دیزاینکراوە بۆ",
    "Distributed data with real-time HQ sync": "داتای دابەشکراو لەگەڵ هاوکاتکردنی ڕاستەوخۆی سەرەکی",
    "Dynamic Loans": "قەرزی داینامیکی",
    "Elevating digital banking with state-of-the-art security, stunning interactive design, and powerful next-gen features.": "بەرزکردنەوەی بانکی دیجیتاڵی بە ئاسایشی پێشکەوتوو، دیزاینی کارلێککەری سەرسوڕهێنەر، و تایبەتمەندییە بەهێزەکانی نەوەی داهاتوو.",
    "Escrow Transfers": "گواستنەوەی پارێزراو",
    "Every feature has been meticulously crafted to provide you with the most seamless and secure financial experience available.": "هەموو تایبەتمەندییەک بە وردی دروستکراوە بۆ ئەوەی بێ کێشەترین و پارێزراوترین ئەزموونی داراییت پێشکەش بکات.",
    "Excellence": "نایابی",
    "Experience the": "ئەزموونی بکە",
    "Experience the future of digital banking with military-grade security and a beautiful interface.": "ئەزموونی داهاتووی بانکی دیجیتاڵی بکە بە ئاسایشی ئاستی سەربازی و ڕووکارێکی جوانەوە.",
    "Explore Features": "گەڕان بەناو تایبەتمەندییەکان",
    "Features": "تایبەتمەندییەکان",
    "Financial Journey": "گەشتی داراییت",
    "Fraud Security": "ئاسایشی ساختەکاری",
    "Future of Banking": "داهاتووی بانکداری",
    "Generate secure virtual and physical cards instantly. Features auto-generated unique PINs, custom spending limits, and one-tap freeze controls.": "دەستبەجێ کارتی گریمانەیی و فیزیکی پارێزراو دروست بکە. تایبەتمەندییەکانی پین کۆدی ناوازەی ئۆتۆماتیکی، سنووری خەرجکردنی تایبەت، و سڕکردن بە یەک کرتە لەخۆدەگرێت.",
    "Get Started Now": "ئێستا دەستپێبکە",
    "Heuristic Fraud Engine": "بزوێنەری ساختەکاری هیرۆستیک",
    "Hidden Fees": "کرێی شاراوە",
    "Instant transfers available 24/7": "گواستنەوەی خێرا بەردەستە ٢٤/٧",
    "Legal": "یاسایی",
    "Multi-currency support across 50+ currencies": "پاڵپشتی فرە دراو بۆ زیاتر لە ٥٠ دراو",
    "Never miss a beat. Real-time, actionable notifications keep you updated on transfer requests, card activations, and vital security alerts.": "هەرگیز هیچت لەدەست نەچێت. ئاگادارکردنەوەی ڕاستەقینە و کردارەکی ئاگادارت دەکاتەوە لە داواکارییەکانی گواستنەوە، چالاککردنی کارت، و ئاگادارییە گرنگەکانی ئاسایش.",
    "Distributed Bank Platform. All rights reserved. Banking services are simulated for demonstration purposes.": "پلاتفۆرمی Distributed Bank. هەموو مافێک پارێزراوە. خزمەتگوزارییە بانکییەکان بۆ مەبەستی تاقیکردنەوە هاوشێوە کراون.",
    "Open a free account in under 2 minutes. Choose your nearest branch location.": "لە کەمتر لە ٢ خولەکدا هەژمارێکی بێبەرامبەر بکەرەوە. نزیکترین لقی خۆت هەڵبژێرە.",
    "Personal Loans": "قەرزی کەسی",
    "Platform": "پلاتفۆرم",
    "Premium Member": "ئەندامی نایاب",
    "Press Kit": "کەلوپەلی ڕۆژنامەوانی",
    "Processed Safely": "بە پارێزراوی جێبەجێکرا",
    "Reimagined": "سەرلەنوێ داڕێژراوەتەوە",
    "Seamless mobile & web experience": "ئەزموونێکی بێ کێشە لە مۆبایل و وێب",
    "Send money with total confidence. Funds are held securely until the recipient explicitly accepts the transfer, preventing accidental sending.": "پارە بنێرە بە متمانەی تەواوەوە. پارەکان بە پارێزراوی دەمێننەوە تا وەرگر بە ڕوونی گواستنەوەکە پەسەند دەکات، ئەمەش ڕێگری دەکات لە ناردنی هەڵە.",
    "Sleep easy仔细 knowing our 6-layer heuristic fraud detection engine monitors velocity, time anomalies, and transaction deviations 24/7.": "بە ئارامی بخەوە بە زانینی ئەوەی کە بزوێنەری دۆزینەوەی ساختەکارییە ٦-چینییەکەمان چاودێری خێرایی، نائاسایی کات، و لادانی مامەڵەکان دەکات ٢٤/٧.",
    "Smart Cards": "کارتی زیرەک",
    "Start Your": "دەستپێبکە بە",
    "Strict KYC Protocol": "پرۆتۆکۆڵی توندی KYC",
    "Transfers & Payments": "گواستنەوە و پارەدان",
    "Uptime Guarantee": "گەڕەنتی بەردەستبوون",
    "Virtual debit card issued instantly": "کارتی دیبیتی گریمانەیی دەستبەجێ دەردەکرێت",
    "We maintain a safe ecosystem. Mandatory passport and national ID verification ensures platform integrity and regulatory compliance.": "ئێمە ژینگەیەکی سەلامەت دەپارێزین. پەسەندکردنی ناچاری پاسپۆرت و پێناسەی نیشتیمانی دڵنیایی دەدات لە یەکپارچەیی پلاتفۆرمەکە و پابەندبوون بە یاساکان.",
    "v2.0 Architecture Deployed": "بنیاتنانی v2.0 جێبەجێکرا",
    "Sleep easy knowing our 6-layer heuristic fraud detection engine monitors velocity, time anomalies, and transaction deviations 24/7.": "بە ئارامی بخەوە بە زانینی ئەوەی کە بزوێنەری دۆزینەوەی ساختەکارییە ٦-چینییەکەمان چاودێری خێرایی، نائاسایی کات، و لادانی مامەڵەکان دەکات ٢٤/٧."
}

with open('missing_keys.json', 'r', encoding='utf-8') as f:
    missing = json.load(f)

with open('lang/ckb.json', 'r', encoding='utf-8') as f:
    ckb = json.load(f)

for k in missing:
    ckb[k] = translations.get(k, k)

with open('lang/ckb.json', 'w', encoding='utf-8') as f:
    json.dump(ckb, f, ensure_ascii=False, indent=4)
print("Updated ckb.json successfully!")
