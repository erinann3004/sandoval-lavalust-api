import { useEffect, useState } from 'react';

const API_BASE = (import.meta.env.VITE_API_BASE_URL || 'http://localhost:3000/api').replace(/\/$/, '');
const emptyProduct = { product_name: '', description: '', price: '', quantity: '' };

function readSession() {
  try {
    return JSON.parse(sessionStorage.getItem('stockroom-session') || 'null');
  } catch {
    return null;
  }
}

async function request(path, options = {}, session = readSession(), canRefresh = true) {
  const headers = new Headers(options.headers || {});
  if (options.body && !(options.body instanceof FormData)) headers.set('Content-Type', 'application/json');
  if (session?.tokens?.access_token) headers.set('Authorization', `Bearer ${session.tokens.access_token}`);
  let response = await fetch(`${API_BASE}${path}`, { ...options, headers });

  if (response.status === 401 && canRefresh && session?.tokens?.refresh_token) {
    const refreshed = await fetch(`${API_BASE}/refresh`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refresh_token: session.tokens.refresh_token }),
    });
    if (refreshed.ok) {
      const result = await refreshed.json();
      const updated = { ...session, tokens: result.tokens };
      sessionStorage.setItem('stockroom-session', JSON.stringify(updated));
      return request(path, options, updated, false);
    }
  }

  const payload = await response.json().catch(() => ({}));
  if (!response.ok) throw new Error(payload.error || `Request failed (${response.status})`);
  return payload;
}

function App() {
  const [session, setSession] = useState(readSession);
  const [mode, setMode] = useState('login');
  const [products, setProducts] = useState([]);
  const [draft, setDraft] = useState(emptyProduct);
  const [editingId, setEditingId] = useState(null);
  const [loading, setLoading] = useState(Boolean(readSession()));
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');

  async function loadProducts(currentSession = session) {
    setLoading(true);
    setError('');
    try {
      const result = await request('/products', {}, currentSession);
      setProducts(result.products || []);
    } catch (loadError) {
      setError(loadError.message);
      if (/unauthorized|refresh token/i.test(loadError.message)) logout(false);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    if (session) loadProducts(session);
  }, [session]);

  function logout(sendRequest = true) {
    const current = readSession();
    if (sendRequest && current?.tokens?.refresh_token) {
      request('/logout', { method: 'POST', body: JSON.stringify({ refresh_token: current.tokens.refresh_token }) }, current).catch(() => {});
    }
    sessionStorage.removeItem('stockroom-session');
    setSession(null);
    setProducts([]);
    setEditingId(null);
    setDraft(emptyProduct);
  }

  async function handleAuth(event) {
    event.preventDefault();
    const formElement = event.currentTarget;
    setSaving(true);
    setError('');
    const form = new FormData(formElement);
    const body = {
      email: form.get('email'),
      password: form.get('password'),
      ...(mode === 'register' ? {
        firstname: form.get('firstname'),
        lastname: form.get('lastname'),
        username: form.get('username'),
      } : {}),
    };
    try {
      const result = await request(`/${mode}`, { method: 'POST', body: JSON.stringify(body) }, null, false);
      if (mode === 'register') {
        setMode('login');
        setNotice(result.message || 'Account created. Please sign in.');
        formElement.reset();
        return;
      }
      const value = { user: result.user, tokens: result.tokens };
      sessionStorage.setItem('stockroom-session', JSON.stringify(value));
      setSession(value);
      setNotice('Welcome back.');
    } catch (authError) {
      setError(authError.message);
    } finally {
      setSaving(false);
    }
  }

  async function saveProduct(event) {
    event.preventDefault();
    const startedAt = Date.now();
    setSaving(true);
    setError('');
    try {
      const editing = editingId !== null;
      await request(editing ? `/products/${editingId}` : '/products', {
        method: editing ? 'PUT' : 'POST',
        body: JSON.stringify({ ...draft, price: Number(draft.price), quantity: Number(draft.quantity) }),
      });
      if (!editing) {
        const remainingTime = 3000 - (Date.now() - startedAt);
        if (remainingTime > 0) await new Promise((resolve) => setTimeout(resolve, remainingTime));
      }
      setDraft(emptyProduct);
      setEditingId(null);
      setNotice(editing ? 'Product updated.' : 'Product added.');
      await loadProducts();
    } catch (saveError) {
      setError(saveError.message);
    } finally {
      setSaving(false);
    }
  }

  function startEdit(product) {
    setEditingId(product.id);
    setDraft({ product_name: product.product_name, description: product.description, price: product.price, quantity: product.quantity });
    setError('');
    document.querySelector('#product-name')?.focus();
  }

  async function removeProduct(product) {
    if (!window.confirm(`Delete “${product.product_name}”? This cannot be undone.`)) return;
    setError('');
    try {
      await request(`/products/${product.id}`, { method: 'DELETE' });
      setNotice('Product deleted.');
      await loadProducts();
    } catch (deleteError) {
      setError(deleteError.message);
    }
  }

  if (!session) {
    return (
      <main className="auth-layout">
        <section className="auth-story">
          <span className="brand-mark">S</span>
          <p className="eyebrow">Stockroom / Product control</p>
          <h1>Know what<br />you have.</h1>
          <p className="story-copy">A clear, dependable view of the products that keep your work moving.</p>
          <div className="story-index"><span>01</span><i /><span>Inventory</span><span className="index-note">Management system</span></div>
        </section>
        <section className="auth-panel">
          <div className="panel-top"><span>LAB 06</span><span>API CONNECTED</span></div>
          <div className="auth-form-wrap">
            <p className="eyebrow">{mode === 'login' ? 'Your workspace' : 'New workspace access'}</p>
            <h2>{mode === 'login' ? 'Sign in' : 'Create account'}</h2>
            <p className="form-intro">{mode === 'login' ? 'Enter your credentials to continue.' : 'Create an account to manage products.'}</p>
            {error && <p className="alert error-alert" role="alert">{error}</p>}
            <form className="auth-form" onSubmit={handleAuth}>
              {mode === 'register' && <>
                <label>First name<input name="firstname" autoComplete="given-name" maxLength="100" required /></label>
                <label>Last name<input name="lastname" autoComplete="family-name" maxLength="100" required /></label>
                <label>Username<input name="username" autoComplete="username" maxLength="100" required /></label>
              </>}
              <label>Email address<input name="email" type="email" autoComplete="email" required /></label>
              <label>Password<input name="password" type="password" minLength="8" autoComplete={mode === 'login' ? 'current-password' : 'new-password'} required /></label>
              <button className="primary-button full-button" disabled={saving}>{saving ? 'Please wait…' : mode === 'login' ? 'Sign in' : 'Create account'}<span aria-hidden="true">↗</span></button>
            </form>
            <p className="switch-mode">{mode === 'login' ? 'New here?' : 'Already registered?'} <button onClick={() => { setMode(mode === 'login' ? 'register' : 'login'); setError(''); }}>{mode === 'login' ? 'Create an account' : 'Sign in'}</button></p>
          </div>
          <p className="auth-foot">SECURE ACCESS <span>JWT AUTHENTICATION</span></p>
        </section>
      </main>
    );
  }

  return (
    <main className="app-shell">
      <header className="topbar">
        <a className="wordmark" href="#top"><span className="brand-mark small-mark">S</span> STOCKROOM</a>
        <div className="topbar-right"><span className="connection-dot" /> API CONNECTED <span className="topbar-divider" /> <span className="user-name">{session.user?.name || session.user?.email}</span><button className="text-button" onClick={() => logout()}>Log out <span aria-hidden="true">↗</span></button></div>
      </header>
      <section className="page-heading" id="top">
        <div><p className="eyebrow">INVENTORY / OVERVIEW</p><h1>Products<span className="heading-count">{products.length.toString().padStart(2, '0')}</span></h1><p className="heading-caption">Manage the items in your catalogue.</p></div>
        <div className="date-stamp"><span>WORKSPACE</span><strong>PRODUCTS</strong><span>AUTHENTICATED SESSION</span></div>
      </section>

      {(error || notice) && <div className={`alert ${error ? 'error-alert' : 'notice-alert'}`} role="status">{error || notice}<button aria-label="Dismiss message" onClick={() => { setError(''); setNotice(''); }}>×</button></div>}

      <section className="workspace-grid">
        <section className="catalogue-panel">
          <div className="section-bar"><div><p className="eyebrow">CATALOGUE</p><h2>All products</h2></div><button className="quiet-button" onClick={() => loadProducts()} aria-label="Refresh products" title="Refresh products">↻</button></div>
          <div className="table-head"><span>PRODUCT</span><span>PRICE</span><span>QTY</span><span>ADDED</span><span>ACTIONS</span></div>
          {loading ? <div className="empty-state"><span className="loader" />Loading products…</div> : products.length === 0 ? <div className="empty-state"><span className="empty-symbol">＋</span><strong>Your catalogue is empty</strong><span>Add your first product to get started.</span></div> : products.map((product) => (
            <article className="product-row" key={product.id}>
              <div className="product-identity"><span className="product-initial">{product.product_name.charAt(0).toUpperCase()}</span><div><strong>{product.product_name}</strong><p>{product.description}</p></div></div>
              <span className="price-value">₱{Number(product.price).toFixed(2)}</span>
              <span className="quantity-value">{product.quantity}<small> units</small></span>
              <span className="date-value">{product.created_at ? new Date(product.created_at.replace(' ', 'T') + (product.created_at.includes('Z') ? '' : 'Z')).toLocaleDateString() : '—'}</span>
              <div className="row-actions"><button title="Edit product" aria-label={`Edit ${product.product_name}`} onClick={() => startEdit(product)}>Edit</button><button className="delete-action" title="Delete product" aria-label={`Delete ${product.product_name}`} onClick={() => removeProduct(product)}>Delete</button></div>
            </article>
          ))}
          <div className="catalogue-foot"><span>SHOWING {products.length} {products.length === 1 ? 'ITEM' : 'ITEMS'}</span><span>PRODUCT MANAGEMENT SYSTEM <i /></span></div>
        </section>

        <aside className="editor-panel">
          <div className="section-bar"><div><p className="eyebrow">{editingId ? `EDITING / #${editingId}` : 'CATALOGUE'}</p><h2>{editingId ? 'Update product' : 'Add a product'}</h2></div><span className="editor-index">{editingId ? '02' : '01'}</span></div>
          <form className="product-form" onSubmit={saveProduct}>
            <label htmlFor="product-name">Product name<input id="product-name" value={draft.product_name} onChange={(event) => setDraft({ ...draft, product_name: event.target.value })} maxLength="100" placeholder="e.g. Field notebook" required /></label>
            <label htmlFor="product-description">Description<textarea id="product-description" value={draft.description} onChange={(event) => setDraft({ ...draft, description: event.target.value })} placeholder="Add a short product description" required /></label>
            <div className="split-fields"><label htmlFor="product-price">Price<input id="product-price" type="number" min="0" step="0.01" value={draft.price} onChange={(event) => setDraft({ ...draft, price: event.target.value })} placeholder="0.00" required /></label><label htmlFor="product-quantity">Quantity<input id="product-quantity" type="number" min="0" step="1" value={draft.quantity} onChange={(event) => setDraft({ ...draft, quantity: event.target.value })} placeholder="0" required /></label></div>
            <button className="primary-button full-button" disabled={saving}>{saving ? 'Saving…' : editingId ? 'Save changes' : 'Add to catalogue'}<span aria-hidden="true">↗</span></button>
            {editingId && <button className="cancel-button" type="button" onClick={() => { setEditingId(null); setDraft(emptyProduct); }}>Cancel editing</button>}
          </form>
          <div className="editor-note"><span className="note-icon">i</span><p>Product details are saved to your authenticated API workspace.</p></div>
        </aside>
      </section>
      <footer className="page-footer"><span>STOCKROOM <i /> LAVALUST API</span><span>LABORATORY EXERCISE NO. 6</span></footer>
    </main>
  );
}

export default App;