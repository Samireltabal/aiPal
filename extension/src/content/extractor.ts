/**
 * Page extractor — designed to be injected via chrome.scripting.executeScript
 * with `func: extractFromPage`. Does NOT live as a registered content_script,
 * so no broad host_permissions are needed; runs only on user gesture in the
 * active tab via the activeTab permission.
 *
 * Kept dependency-free so it can be serialized into a single function body.
 * (Mozilla Readability would require a separate bundled content_script and a
 *  web_accessible_resources entry — deferred to a future iteration.)
 */

export type Extracted = {
  url: string;
  title: string;
  selection: string | null;
  article: string | null;
};

const SELECTION_MAX = 50_000;
const ARTICLE_MAX = 100_000;

export function extractFromPage(): Extracted {
  const url = location.href;
  const title = document.title || url;

  const sel = window.getSelection()?.toString().trim() ?? '';
  const selection = sel ? sel.slice(0, SELECTION_MAX) : null;

  // Prefer <article> > <main> > body — gives noticeably cleaner text on most sites.
  const root =
    document.querySelector('article') ??
    document.querySelector('main') ??
    document.body;

  const text = (root as HTMLElement | null)?.innerText?.trim() ?? '';
  const article = text ? text.slice(0, ARTICLE_MAX) : null;

  return { url, title, selection, article };
}
