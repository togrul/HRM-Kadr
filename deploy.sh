#!/bin/bash

# Ask for a commit message
echo "Enter the commit message:"
read commit_message

# Add all changes to git
git add .

# Commit changes
git commit -m "$commit_message"

git push origin main

# Execute commands on the remote server
ssh toor@172.31.31.38 << 'EOF'
cd /home/toor/hr-crm
git pull origin main --force
php artisan migrate
php artisan cache:clear
php artisan config:clear
php artisan view:clear
EOF
