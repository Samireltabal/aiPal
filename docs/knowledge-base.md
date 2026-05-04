# Knowledge Base (RAG)

aiPal supports Retrieval-Augmented Generation (RAG) by allowing you to upload personal documents (Markdown, plain text, or code files) into your private knowledge base. The assistant can then answer questions grounded in your uploaded content using semantic search (pgvector).

## Uploading Documents

1. In the web UI, go to **Knowledge Base** (or via chat: "upload my notes").
2. Drag & drop or select files (`.md`, `.txt`, `.rst`, common code extensions).
3. Files are processed, chunked, embedded, and stored.
4. You can view, search, or delete uploaded documents from the UI.

**Supported formats:** Markdown, plain text, and most code files. Large files are split into chunks automatically.

## Querying Your Knowledge Base

Once documents are uploaded, simply ask natural questions in chat:

- "What does the spec say about authentication?"
- "Summarize the key points from my Q3 meeting notes"
- "Find references to project deadlines in my uploaded files"

The AI will automatically use the `SearchKnowledgeBase` tool when relevant (if enabled in Settings → AI Functions).

## Tips

- Use descriptive filenames for better context.
- Re-upload updated versions of documents to refresh the index.
- Export/import your knowledge base via the Memory section if needed.
- For best results, keep documents focused and well-structured.

See the [New User Guide](../README.md#first-chat-examples) for example prompts involving the knowledge base.
