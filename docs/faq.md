# aiPal FAQ

## What AI providers are supported?
aiPal supports Anthropic (Claude), OpenAI, DeepSeek, xAI (Grok), Gemini, and Ollama for local/offline models. For full details, see the README's "AI & Chat" section.

## Can I run aiPal without cloud AI?
Yes! Using the Ollama integration, you can run local models without needing external API keys or internet access.

## How do I invite a new user?
If you're an admin, generate an invite link from the dashboard and share it with your intended user.

## How is my data stored?
All data is stored in your self-hosted PostgreSQL database. AI provider API keys and settings are managed in the .env file.

## I encountered an error during setup. What should I do?
Check the [Troubleshooting Guide](./troubleshooting.md) for common solutions. For more help, open a GitHub issue.

## Can aiPal be deployed behind a proxy?
Yes, but ensure WebSocket and webhook endpoints are forwarded correctly. For more details, see the deployment guide.

## Where can I find all the documentation?
See the [Documentation Index](./index.md) for a complete list of guides covering setup, integrations, API, architecture, and operations. The main README also links to key sections.
