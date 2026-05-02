# Architecture Overview

The aiPal application is built using the Laravel framework, supporting a range of integrations and features to enhance productivity and management capabilities.

## System Design

- **Frontend**: Built with Tailwind v4 and Livewire, providing a dynamic and responsive user interface.
- **Backend**: Laravel 13 serves as the backbone of the application, handling requests, processing data, and serving views.
- **Database**: Utilizes PostgreSQL 16 with pgvector for advanced memory and knowledge functionalities.
- **Caching**: Redis is used for caching to optimize performance and reduce response times.

## Module Layout

- **AI & Chat**: This module leverages various AI providers for NLP and chat functionalities.
- **Memory & Knowledge**: Employs pgvector for semantic searches across conversations and uploaded documents.
- **Voice**: Whisper STT and OpenAI/EievenLabs TTS for voice interactions.
- **Productivity**: Tools for notes, reminders, tasks, and daily briefings make up the productivity module.
- **Integrations**: Includes setup for Google Calendar, Telegram, WhatsApp, Jira, GitLab, and Gmail integrations.

## Data Flow

Data flows through several stages in aiPal:
1. **Request Handling**: Initiated by user actions or scheduled jobs.
2. **Processing**: Involves AI model interactions and decision logic.
3. **Response Generation**: Composes responses utilizing AI and memory.

## DB Schema

The database schema is designed to support multidimensional interactions, storing data in relational tables with support for document-based searches via pgvector.

For more detailed diagrams and schema, refer to the internal development documentation.