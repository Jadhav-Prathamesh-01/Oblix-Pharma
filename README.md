# Oblix WordPress Theme - Docker Setup

This WordPress theme uses Docker for local development.

## Prerequisites
- Docker Desktop installed on your Mac

## Quick Start

1. **Start WordPress**
   ```bash
   docker-compose up -d
   ```

2. **Access WordPress**
   - Open browser: http://localhost:8080
   - Complete WordPress installation (takes 2 minutes)
   - Language: Choose your language
   - Site Title: Oblix Health (or any name)
   - Username: admin
   - Password: (choose a strong password)
   - Email: your@email.com

3. **Activate Theme**
   - Go to: http://localhost:8080/wp-admin
   - Login with your credentials
   - Navigate to: Appearance → Themes
   - Activate "Oblix Health" theme
   - Visit homepage: http://localhost:8080

4. **Make Changes**
   - Edit any file in the oblix folder
   - Refresh browser to see changes instantly!

## Useful Commands

```bash
# Stop WordPress
docker-compose down

# Start WordPress
docker-compose up -d

# View logs
docker-compose logs -f wordpress

# Restart WordPress
docker-compose restart wordpress

# Stop and remove everything (including database)
docker-compose down -v
```

## Troubleshooting

- If port 8080 is busy, change it in docker-compose.yml (e.g., "8081:80")
- If video doesn't load, make sure heros-bg.mp4 is in the theme folder
