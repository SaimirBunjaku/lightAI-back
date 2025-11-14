# Energy AI - Complete Setup Guide

This guide will walk you through setting up the Energy AI API project on a **completely fresh** macOS or Windows machine from scratch.

## Table of Contents
- [macOS Setup (Free Tools)](#macos-setup-free-tools)
- [Windows Setup (Using Laragon)](#windows-setup-using-laragon)
- [Getting Your Gemini API Key](#getting-your-gemini-api-key)
- [Testing the API](#testing-the-api)
- [Troubleshooting](#troubleshooting)

---

## macOS Setup (Free Tools)

### Prerequisites
Starting with a **completely fresh Mac** - we'll install everything from scratch.

---

### Step 1: Install Homebrew (Package Manager)

Open **Terminal** (Cmd + Space, type "Terminal") and run:

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

Follow the prompts. After installation, run the commands it suggests to add Homebrew to your PATH.

Verify installation:
```bash
brew --version
```

---

### Step 2: Install PHP

```bash
brew install php@8.2
```

Verify:
```bash
php --version
# Should show: PHP 8.2.x
```

---

### Step 3: Install Composer

```bash
brew install composer
```

Verify:
```bash
composer --version
# Should show: Composer version 2.x.x
```

---

### Step 4: Install MySQL (Database)

```bash
brew install mysql
```

Start MySQL:
```bash
brew services start mysql
```

Secure MySQL installation (optional but recommended):
```bash
mysql_secure_installation
```
- Press Enter for no root password (or set one if you prefer)
- Answer 'Y' to other questions

Verify MySQL is running:
```bash
mysql -u root
# You should see: mysql>
# Type: exit
```

---

### Step 5: Install Database Management Tool (Sequel Ace)

Download and install **Sequel Ace** (free, open-source):
- Visit: [https://sequel-ace.com/](https://sequel-ace.com/)
- Download and drag to Applications folder
- Open Sequel Ace
- Click "Connect" with default settings:
  - Host: `127.0.0.1`
  - Username: `root`
  - Password: (leave empty unless you set one)

---

### Step 6: Install Git

```bash
brew install git
```

Verify:
```bash
git --version
```

---

### Step 7: Install Node.js (for future frontend needs)

```bash
brew install node
```

Verify:
```bash
node --version
npm --version
```

---

### Step 8: Clone the Repository

```bash
# Create a projects directory
mkdir ~/Projects
cd ~/Projects

# Clone the repository
git clone <your-repository-url> energy-ai
cd energy-ai
```

---

### Step 9: Install PHP Dependencies

```bash
composer install
```

This may take a few minutes.

---

### Step 10: Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

---

### Step 11: Create Database

**Option A: Using Sequel Ace (GUI)**
1. Open Sequel Ace
2. Connect to your database
3. Click "Database" → "Add Database"
4. Name it: `energy_ai`

**Option B: Using Terminal**
```bash
mysql -u root -e "CREATE DATABASE energy_ai;"
```

---

### Step 12: Configure Database in .env

Open `.env` file in a text editor:
```bash
nano .env
# or
open -a TextEdit .env
```

Update these lines:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=energy_ai
DB_USERNAME=root
DB_PASSWORD=
```

Save and close (in nano: Ctrl+X, then Y, then Enter).

---

### Step 13: Get Gemini API Key

See [Getting Your Gemini API Key](#getting-your-gemini-api-key) section below.

Add your API key to `.env`:
```env
GEMINI_API_KEY=your-actual-api-key-here
```

---

### Step 14: Run Migrations

```bash
php artisan migrate
```

You should see:
```
Migration table created successfully.
Migrating: 2025_11_08_205402_create_device_analyses_table
Migrated:  2025_11_08_205402_create_device_analyses_table (45ms)
```

---

### Step 15: Create Storage Link

```bash
php artisan storage:link
```

---

### Step 16: Start the Development Server

```bash
php artisan serve
```

Your API is now running at: **http://127.0.0.1:8000**

---

### Step 17: Test Your Setup

Open a **new Terminal window** (keep the server running in the first one) and test:

```bash
curl http://127.0.0.1:8000/api/health
```

Expected response:
```json
{"status":"ok","timestamp":"2025-11-08T20:59:41+00:00"}
```

✅ **Success! Your macOS setup is complete!**

---

## Windows Setup (Using Laragon)

### Prerequisites
Starting with a **completely fresh Windows PC** - we'll install everything from scratch.

---

### Step 1: Download and Install Laragon

1. Visit: [https://laragon.org/download/](https://laragon.org/download/)
2. Download **Laragon Full** (Recommended - includes everything)
3. Run the installer
4. **Important:** Use these settings during installation:
   - Installation path: `C:\laragon` (default)
   - ✅ Check "Auto virtual hosts"
   - ✅ Check "Add Laragon to PATH"

---

### Step 2: Install Git (if not already installed)

**Check if Git is installed:**
1. Press `Win + R`
2. Type `cmd` and press Enter
3. Type: `git --version`

**If Git is not installed:**
1. Download from: [https://git-scm.com/download/win](https://git-scm.com/download/win)
2. Run installer with default settings
3. Restart your computer

---

### Step 3: Install Node.js (for future frontend needs)

1. Visit: [https://nodejs.org/](https://nodejs.org/)
2. Download **LTS version** (recommended)
3. Run installer with default settings
4. Verify in Command Prompt:
```bash
node --version
npm --version
```

---

### Step 4: Launch Laragon

1. Open Laragon (Start menu → Laragon)
2. Click **"Start All"** button
3. Wait for Apache and MySQL to show **green icons**

**What Laragon Includes:**
- ✅ PHP (multiple versions)
- ✅ Apache web server
- ✅ MySQL database
- ✅ Composer
- ✅ HeidiSQL (database management tool)
- ✅ Terminal with everything pre-configured

---

### Step 5: Clone the Repository

**Option A: Using Laragon Terminal** (Recommended)
1. Right-click Laragon icon (system tray)
2. Click **"Terminal"**
3. Run:
```bash
cd C:\laragon\www
git clone <your-repository-url> energy-ai
cd energy-ai
```

**Option B: Manual Download**
1. Download the project ZIP
2. Extract to: `C:\laragon\www\energy-ai`

---

### Step 6: Install PHP Dependencies

In Laragon Terminal:
```bash
composer install
```

This may take a few minutes.

---

### Step 7: Environment Configuration

```bash
# Copy environment file
copy .env.example .env

# Generate application key
php artisan key:generate
```

---

### Step 8: Create Database

**Option A: Using HeidiSQL (GUI - Recommended)**
1. Right-click Laragon → **Database** → **MySQL** → **HeidiSQL**
2. Click **"New"** button at the bottom left
3. Right-click **"Unnamed"** → **Create new** → **Database**
4. Name it: `energy_ai`
5. Click **OK**

**Option B: Using MySQL Console**
1. Right-click Laragon → **MySQL** → **MySQL Console**
2. Type: `CREATE DATABASE energy_ai;`
3. Press Enter
4. Type: `exit`

---

### Step 9: Configure Database in .env

Open `.env` in Notepad:
```bash
notepad .env
```

Update these lines:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=energy_ai
DB_USERNAME=root
DB_PASSWORD=
```

Save and close (File → Save).

---

### Step 10: Get Gemini API Key

See [Getting Your Gemini API Key](#getting-your-gemini-api-key) section below.

Add your API key to `.env`:
```env
GEMINI_API_KEY=your-actual-api-key-here
```

---

### Step 11: Run Migrations

```bash
php artisan migrate
```

You should see:
```
Migration table created successfully.
Migrating: 2025_11_08_205402_create_device_analyses_table
Migrated:  2025_11_08_205402_create_device_analyses_table (45ms)
```

---

### Step 12: Create Storage Link

```bash
php artisan storage:link
```

---

### Step 13: Configure Pretty URL (Automatic)

Laragon automatically creates a pretty URL:
```
http://energy-ai.test
```

**If it doesn't work automatically:**
1. Right-click Laragon → **Apache** → **Add Virtual Host**
2. Enter name: `energy-ai.test`
3. Enter path: `C:\laragon\www\energy-ai\public`
4. Click **OK**
5. Click **"Start All"** again

---

### Step 14: Test Your Setup

Open browser and go to:
```
http://energy-ai.test/api/health
```

Expected response:
```json
{"status":"ok","timestamp":"2025-11-08T20:59:41+00:00"}
```

✅ **Success! Your Windows setup is complete!**

---

## Getting Your Gemini API Key

### Step 1: Go to Google AI Studio
Visit: [https://aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey)

### Step 2: Sign In
Use your Google account to sign in.

### Step 3: Create API Key
1. Click **"Create API Key"**
2. Select **"Create API key in new project"** or choose existing project
3. Copy the generated API key (starts with `AIza...`)

### Step 4: Add to .env
Open your `.env` file and add:
```env
GEMINI_API_KEY=AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
```

**Important:**
- ✅ Free tier: 1,500 requests/day
- ✅ No credit card required

---

## Testing the API

### Method 1: Using Browser (Quick Test)

Simply open in your browser:

**macOS:**
```
http://127.0.0.1:8000/api/health
http://127.0.0.1:8000/api/device/categories
```

**Windows:**
```
http://energy-ai.test/api/health
http://energy-ai.test/api/device/categories
```

---

### Method 2: Using cURL (Command Line)

**Test Health Endpoint:**
```bash
# macOS
curl http://127.0.0.1:8000/api/health

# Windows (Laragon Terminal)
curl http://energy-ai.test/api/health
```

**Test Device Categories:**
```bash
# macOS
curl http://127.0.0.1:8000/api/device/categories

# Windows
curl http://energy-ai.test/api/device/categories
```

**Test Device Analysis (Upload Image):**
```bash
# macOS
curl -X POST http://127.0.0.1:8000/api/device/analyze \
  -F "image=@/Users/yourname/Desktop/laptop.jpg"

# Windows
curl -X POST http://energy-ai.test/api/device/analyze \
  -F "image=@C:\Users\YourName\Desktop\laptop.jpg"
```

**Get Analysis by ID:**
```bash
# macOS
curl http://127.0.0.1:8000/api/device/analysis/1

# Windows
curl http://energy-ai.test/api/device/analysis/1
```

---

### Method 3: Using Postman (Recommended for Testing)

#### Install Postman
Download from: [https://www.postman.com/downloads/](https://www.postman.com/downloads/)

#### Test Device Analysis
1. Open Postman
2. Create a new request
3. Set method to **POST**
4. URL (choose based on your OS):
   - **macOS:** `http://127.0.0.1:8000/api/device/analyze`
   - **Windows:** `http://energy-ai.test/api/device/analyze`
5. Go to **Body** tab
6. Select **form-data**
7. Add key: `image`
8. Change type to **File** (dropdown on right)
9. Click **Select Files** and choose a device photo (laptop, phone, fridge, etc.)
10. Click **Send**

**Expected Response:**
```json
{
  "success": true,
  "message": "Device analyzed successfully",
  "data": {
    "id": 1,
    "device": {
      "category": "laptop",
      "brand": "Apple",
      "model": "MacBook Pro 14-inch (2023)",
      "confidence": "high"
    },
    "energy": {
      "typical_wattage": "30-50W",
      "idle_wattage": "10-15W",
      "active_wattage": "30-50W",
      "daily_kwh": "0.24-0.40",
      "annual_kwh": "87.6-146",
      "estimated_annual_cost": "$13-22"
    },
    "tips": [
      "Enable automatic sleep mode after 10 minutes of inactivity",
      "Reduce display brightness to 70-80% for indoor use",
      "Unplug charger when battery is full to reduce phantom load",
      "Use energy saver mode for better battery efficiency",
      "Close unused applications to reduce CPU load and power consumption"
    ],
    "fallback_level": "specific",
    "reasoning": "Device clearly identified as MacBook Pro based on distinctive design features and Apple logo visible in the image."
  }
}
```

---

### Method 4: Using Insomnia (Alternative to Postman)

#### Install Insomnia
Download from: [https://insomnia.rest/download](https://insomnia.rest/download)

#### Test Device Analysis
1. Create new request
2. Method: **POST**
3. URL:
   - **macOS:** `http://127.0.0.1:8000/api/device/analyze`
   - **Windows:** `http://energy-ai.test/api/device/analyze`
4. Body tab → **Multipart Form**
5. Add form field:
   - **Name:** `image`
   - **Type:** File
   - **Value:** Select your device image
6. Click **Send**

**To use:**

**macOS:**
```
http://127.0.0.1:8000/test.html
```

**Windows:**
```
http://energy-ai.test/test.html
```

---

## API Endpoints Reference

### Base URL

**macOS (using `php artisan serve`):**
```
http://127.0.0.1:8000/api
```

**Windows (using Laragon):**
```
http://energy-ai.test/api
```

### Available Endpoints

| Method | Endpoint | Description | Parameters |
|--------|----------|-------------|------------|
| GET | `/health` | Check API health status | None |
| GET | `/device/categories` | Get supported device categories | None |
| POST | `/device/analyze` | Analyze device image | `image` (file, max 5MB) |
| GET | `/device/analysis/{id}` | Get analysis by ID | `id` (URL parameter) |

---

## Troubleshooting

### macOS Issues

#### "composer: command not found"
```bash
brew install composer
```

#### "php: command not found"
```bash
brew install php@8.2
brew link php@8.2
```

#### MySQL not starting
```bash
brew services restart mysql
# Check status
brew services list
```

#### Port 8000 already in use
```bash
# Use different port
php artisan serve --port=8080
```

#### Database connection refused
```bash
# Make sure MySQL is running
brew services start mysql

# Test connection
mysql -u root
```

---

### Windows Issues

#### Laragon won't start
1. Close Laragon completely
2. Right-click Laragon → **Run as Administrator**
3. Click **Start All**

#### "composer: command not found" in regular Command Prompt
- Always use **Laragon Terminal** (right-click Laragon → Terminal)
- Laragon Terminal has everything pre-configured

#### Apache or MySQL won't start (Red icon)
**Port conflict:**
1. Right-click Laragon → **Apache** → **Change Port** to 8080
2. Right-click Laragon → **MySQL** → **Change Port** to 3307
3. Update `.env`:
```env
DB_PORT=3307
```

**Firewall blocking:**
- Allow Apache and MySQL through Windows Firewall when prompted

#### Virtual host not working
```bash
# In Laragon Terminal
notepad C:\Windows\System32\drivers\etc\hosts
```
Add this line:
```
127.0.0.1  energy-ai.test
```
Save and restart Laragon.

---

### General Issues

#### "GEMINI_API_KEY not set"
```bash
# Clear cache
php artisan config:clear

# Verify .env file has:
# GEMINI_API_KEY=your-actual-key
```

#### "Class 'Gemini\Data\Blob' not found"
```bash
composer dump-autoload
```

#### Images not uploading / 500 error
```bash
# macOS
chmod -R 775 storage
chmod -R 775 bootstrap/cache
php artisan storage:link

# Windows
# Run Laragon Terminal as Administrator, then:
php artisan storage:link
```

#### Database connection error
1. Check MySQL is running
2. Verify `.env` database credentials
3. Ensure database `energy_ai` exists
4. Clear config cache: `php artisan config:clear`

#### Gemini API quota exceeded
- Free tier: 1,500 requests/day
- Check usage: [Google AI Studio](https://aistudio.google.com/)
- Wait 24 hours for quota reset

---

## What Devices Can Be Analyzed?

The AI can identify and provide energy tips for:

- 💻 **Computers:** Laptops, desktops, monitors, keyboards
- 📺 **Entertainment:** TVs, gaming consoles (PS5, Xbox), streaming devices
- ❄️ **Kitchen Appliances:** Refrigerators, microwaves, ovens, dishwashers, coffee makers
- 🧺 **Laundry:** Washing machines, dryers
- 🌡️ **Climate Control:** Air conditioners, heaters, fans, dehumidifiers
- 🖨️ **Office Equipment:** Printers, scanners, routers, modems
- 📱 **Mobile Devices:** Phone chargers, tablets, smart speakers
- 💡 **Lighting:** Smart bulbs, lamps
- 🏠 **Smart Home:** Thermostats, security cameras, doorbells

---

## System Requirements

### macOS
- macOS 10.15 (Catalina) or higher
- 4GB RAM minimum (8GB recommended)
- 2GB free disk space

### Windows
- Windows 10 or higher (64-bit)
- 4GB RAM minimum (8GB recommended)
- 2GB free disk space

---

## Free Tier Limits

### Google Gemini API (Free)
- ✅ 1,500 requests per day
- ✅ 15 requests per minute
- ✅ Commercial use allowed
- ✅ No credit card required
- ✅ Multimodal (text + images)

### Local Development (Free)
- ✅ PHP (open source)
- ✅ MySQL (open source)
- ✅ Composer (free)
- ✅ Laragon (free)
- ✅ Sequel Ace (free)
- ✅ HeidiSQL (free)

---

## Need Help?

### Check Logs

**macOS:**
```bash
tail -f storage/logs/laravel.log
```

**Windows:**
```bash
# In Laragon Terminal
type storage\logs\laravel.log
```

### Useful Commands

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check routes
php artisan route:list

# Check database connection
php artisan migrate:status

# Re-run migrations (fresh start)
php artisan migrate:fresh
```

### Resources

- Laravel Documentation: [https://laravel.com/docs](https://laravel.com/docs)
- Google Gemini API: [https://ai.google.dev/](https://ai.google.dev/)
- Laragon Documentation: [https://laragon.org/docs/](https://laragon.org/docs/)
- Homebrew (macOS): [https://brew.sh/](https://brew.sh/)
