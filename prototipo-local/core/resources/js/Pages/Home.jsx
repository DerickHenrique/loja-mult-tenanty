import SharedMessage from '../Components/SharedMessage';

export default function Home({ tenant, activeHost }) {
	return (
    <main className="store" style={{ '--brand': tenant.theme.primary, '--accent': tenant.theme.accent, '--surface': tenant.theme.surface }}>
      <header className="topbar"><a href="/" className="wordmark" aria-label={`${tenant.name}, página inicial`}><span className="brand-mark">{tenant.brand_mark}</span>{tenant.name}</a><span className="status">Demonstração local</span></header>
      <section className="hero">
        <p className="eyebrow">{tenant.content.eyebrow}</p>
        <h1>{tenant.content.title}</h1>
        <p className="intro">{tenant.content.description}</p>
        <a className="button" href="#sobre">Conheça a loja <span aria-hidden="true">↗</span></a>
        <div className="hero-art" aria-hidden="true"><div className="orbit orbit-one"/><div className="orbit orbit-two"/><div className="art-center">{tenant.brand_mark}</div><span className="spark spark-a">✳</span><span className="spark spark-b">✦</span></div>
      </section>
      <section className="details" id="sobre">
        <div><p className="eyebrow">Uma base, várias identidades</p><h2>O Core é compartilhado.<br/>A experiência é da sua loja.</h2></div>
        <div className="detail-copy"><p>{tenant.content.body}</p><SharedMessage /></div>
      </section>
      <footer><span>Plataforma de demonstração</span><span className="tenant-pill">Tenant <b>{tenant.id}</b><i/> Tema <b>{tenant.theme.label}</b> · {activeHost}</span></footer>
    </main>
  );
}
