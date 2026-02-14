import sanitizeHtml from "sanitize-html";

export function sanitizePlainText(input: string): string {
  return sanitizeHtml(input, {
    allowedTags: [],
    allowedAttributes: {}
  })
    .replace(/[\u0000-\u001F\u007F]/g, "")
    .trim();
}

export function sanitizeOptionalText(input?: string | null): string | null {
  if (!input) {
    return null;
  }

  const cleaned = sanitizePlainText(input);
  return cleaned.length > 0 ? cleaned : null;
}
