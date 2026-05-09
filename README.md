##copy the code in vscode

git clone https://github.com/Dankord/school-tourism.git

# Project Setup Guide

## 1. Clone the Repository

Open your terminal and run:

```bash
git clone https://github.com/Dankord/school-tourism.git
```

Then navigate into the project folder:

```bash
cd project-folder-name
```

---

## 2. Open in VS Code

You can open the project in Visual Studio Code using:

```bash
code .
```

Or manually:

* Open VS Code
* Click **File > Open Folder**
* Select the cloned project folder

---

## 3. Environment Setup

If the project uses environment variables:

1. Duplicate the example file:

```bash
cp .env.example .env
```

1. Update the `.env` file with your configuration:

* Database credentials
* API keys
* App URL

---

## 4. Database Setup (if applicable)

1. Create a database in your local environment
2. Import the provided SQL file (if any)
3. Update database credentials in `.env`

---

* Make sure all dependencies are installed before running
* Check `.env` configuration if something fails
* Contact the project owner if you encounter issues
