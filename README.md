# StreamHive
StreamHive is a YouTube-style video sharing web app built with plain PHP and a custom MVC structure.
It supports account registration/login, browsing and watching videos, and an admin area for uploading and managing videos.
## What this project is
StreamHive is a server-rendered web application where:
- Guests can browse public videos.
- Registered users can log in and use the platform.
- Admin users can upload videos and thumbnails, set visibility (public/unlisted/private), and edit or delete uploaded videos.
- Video duration is extracted automatically during upload.
The app is organized with a lightweight custom architecture:
- `core/` contains infrastructure like routing and database connection.
- `app/controllers/` handles request flow.
- `app/models/` handles database queries.
- `views/` contains PHP-rendered UI templates.
- `public/` contains the entrypoint, static assets, and uploaded media files.
## Technologies used
- PHP (native PHP, no framework)
- MySQL/MariaDB (via `mysqli` and prepared statements)
- HTML5
- CSS3
- Vanilla JavaScript
- PHP Sessions for authentication and role handling
- `ffprobe` (FFmpeg) for video duration extraction on upload