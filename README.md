# MoodleMoot Netherlands 2026 — Demo Files (Moodle 5.0)
## AI for Learning Analytics and Predictive Insights
### 4-Factor Behavioral Risk Scoring

## Quick Start

```bash
# 1. Start Moodle 5.0
docker compose up -d
# Wait 3-5 minutes, then access http://localhost (admin / MoodleMoot2026!)

# 2. Find your container name
docker ps --format '{{.Names}}'

# 3. Install the custom block plugin
# IMPORTANT: Moodle 5.0 uses /blocks/ (NOT /public/blocks/ - that's 5.1+)
docker cp block_predictive_analytics <container>:/bitnami/moodle/blocks/predictive_analytics

# 4. Run database upgrade
docker exec -u www-data <container> php /bitnami/moodle/admin/cli/upgrade.php --non-interactive

# 5. Populate demo data (200 students, 4 behavioral profiles, 16 weeks)
docker cp populate_demo_data.php <container>:/bitnami/moodle/admin/cli/
docker exec -u www-data <container> php /bitnami/moodle/admin/cli/populate_demo_data.php

# 6. Enable Moodle's built-in analytics
# Site Admin → Analytics → Analytics settings → Enable
# Site Admin → Analytics → Analytics models → Enable "Students at risk" → Evaluate → Get predictions

# 7. Add the block to the DS101 course page
# Navigate to DS101 → Turn editing on → Add block → Predictive Analytics Dashboard
```

## Files

| File | Purpose |
|------|---------|
| `docker-compose.yml` | Moodle 5.0 + MariaDB 10.11 Docker setup |
| `populate_demo_data.php` | Generates 200 students with 4 behavioral profiles across 16 weeks |
| `block_predictive_analytics/` | Custom Moodle block plugin with 4-factor risk scoring engine |
| `standalone-dashboard.html` | Offline fallback dashboard — Moodle-branded, no instance required |
| `Moodle_5.0_Demo_Import_Guide.docx` | Comprehensive step-by-step import guide |

## Key Difference: Moodle 5.0 vs 5.1

| Feature | Moodle 5.0 | Moodle 5.1 |
|---------|-----------|-----------|
| Directory structure | Traditional (`/blocks/`) | New (`/public/blocks/`) |
| Web root | `/moodle/` | `/moodle/public/` |
| Oracle DB | Dropped | Dropped |
| "Never accessed" filter | Not available | Available in Report Builder |
| version.php requires | 2025041400 | 2024100700 |

## System Requirements

| Component | Version |
|-----------|---------|
| Moodle | 5.0 (April 2025) |
| PHP | 8.2+ (8.3 recommended) |
| MariaDB | 10.6.7+ (or MySQL 8.0+ / PostgreSQL 13+) |
| Docker | 20.10+ with Compose V2 |

## Credentials

| Account | Username | Password |
|---------|----------|----------|
| Admin | admin | MoodleMoot2026! |
| Students | demostu001-200 | Demo2026! |
| Email | @moodlemoot-demo.nl | — |
