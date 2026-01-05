#!/bin/bash
cd /c/xampp/htdocs/DBMS

echo "🔍 Git Status:"
git status

echo ""
echo "📝 Add all files..."
git add .

echo ""
echo "💾 Commit..."
git commit -m "Initial commit: DBMS Shop with Product Filter & Performance Comparison

- Multi-condition product filter (search, price, brand, rating)
- Database indexes optimization (9 indexes)
- Performance comparison: With Index vs Without Index
- Prepared statements for security
- Real-time filter execution time tracking
- 41,573 products, 817 brands
- Responsive design with cart & auth system"

echo ""
echo "🚀 Push to GitHub..."
git remote add origin https://github.com/ngynqt/tm_DBMS_final.git 2>/dev/null || git remote set-url origin https://github.com/ngynqt/tm_DBMS_final.git

git branch -M main
git push -u origin main

echo ""
echo "✅ Push complete!"
