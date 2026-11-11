/** jk/v1/admin REST client (cookie auth + X-WP-Nonce). */
const cfg = window.JKSettings || {};

export async function api(path, { method = 'GET', body } = {}) {
  const res = await fetch(cfg.rest + path, {
    method,
    credentials: 'same-origin',
    headers: {
      'X-WP-Nonce': cfg.nonce,
      Accept: 'application/json',
      ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
    },
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });
  let json = null;
  try {
    json = await res.json();
  } catch {
    json = null;
  }
  if (!res.ok) {
    const err = new Error((json && json.message) || `Request failed (${res.status})`);
    err.status = res.status;
    throw err;
  }
  return json;
}

export default cfg;
