# Vocaloid PoC

A proof-of-concept voice synthesis system that allows users to record phonemes and render text using their own voice.

## Concept

A web-based voice synthesis application where users can create a voice profile by recording phonemes, then use that voice to synthesize speech from text.

## Tech Stack

*   **Frontend:** HTML5, CSS (Bootstrap 5.3), JavaScript (ES6)
*   **Backend:** PHP 8.x
*   **Database:** SQLite
*   **Audio:** MediaRecorder API, Web Audio API

## Architecture

Monolithic web application with a PHP backend handling user authentication and API endpoints, and a JavaScript frontend providing UI and audio recording capabilities.

### Components

*   **Authentication:** User signup and account management system (PHP with SQLite).
*   **Audio Recorder:** Client-side phoneme recording functionality (JavaScript MediaRecorder).
*   **Frontend UI:** Bootstrap-based responsive user interface.
*   **Render Engine:** Text-to-speech synthesis using recorded phonemes (PHP backend API endpoint, in progress).

## Current Capabilities

*   User signup and account creation
*   Audio recording with MediaRecorder API
*   Phoneme recording workflow for 13 phonemes
*   Sample upload and storage
*   Text input interface for render requests

## Features

### Primary Features

*   User authentication and account management
*   Phoneme recording wizard
*   Voice sample storage per user
*   Text-to-speech render requests

### Secondary Features

*   Audio preview during recording
*   Bootstrap responsive UI
*   SQLite persistent storage

## API Endpoints

| Method | Endpoint                  | Description                                            |
|--------|---------------------------|--------------------------------------------------------|
| POST   | `/api/signup.php`         | User registration endpoint                             |
| POST   | `/api/upload.php`         | Upload phoneme audio sample                            |
| POST   | `/api/request_render.php` | Request text-to-speech rendering with user's voice     |

## How to Run Locally

1.  **Setup:** Ensure PHP 8.x and SQLite are installed.
2.  **Serve:** Use the PHP built-in server: `php -S localhost:8000` from the project root.
3.  **Access:** Navigate to `http://localhost:8000` in your browser.
4.  **Dependencies:** No external dependencies required - uses native Web APIs and PDO for the database.
5.  **HTTPS:** For microphone access, configure a local SSL certificate. An example `nginx.conf` is provided.

## Development State

The basic MVP structure is complete with signup, recording, and render request interfaces. The audio processing and phoneme synthesis backend needs implementation.

## Open Questions

*   What audio processing library will be used for synthesis?
*   How will phoneme samples be processed and normalized?
*   What is the target quality/sample rate for recordings?
*   Will voice samples be stored on disk or in the database?
*   How will render requests be queued and processed?

## Next Steps

*   Implement phoneme sample upload and storage in `api/upload.php`.
*   Implement the voice synthesis engine in `api/request_render.php`.
*   Add authentication/login functionality.
*   Implement an audio processing pipeline for phoneme samples.
*   Add error handling and validation throughout.
*   Implement a render result delivery mechanism.
