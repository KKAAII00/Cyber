<?php
require_once 'includes/functions.php';
if (current_user()) redirect('dashboard.php');
page_header('Welcome');
?>
<section class="hero">
  <div class="hero-copy">
    <div class="eyebrow">CYBERSECURITY TRAINING NETWORK / 2045</div>
    <h1>Become the next<br><span>Cyber Defender.</span></h1>
    <p>Investigate digital incidents, solve security challenges and protect the fictional world of CyberGuard Academy.</p>
    <div class="hero-actions"><a class="btn primary" href="register.php">Start your campaign</a><a class="btn ghost" href="login.php">Agent login</a></div>
    <div class="hero-tags"><span>01 · STORY MISSIONS</span><span>02 · SAFE SIMULATIONS</span><span>03 · TEAM PLAY</span></div>
  </div>
  <div class="hero-panel"><div class="radar"><div class="radar-core">CG</div></div><div class="status-line"><i></i> TRAINING NETWORK ONLINE</div><div class="hero-stat"><strong>6</strong><span>CAMPAIGN CHAPTERS</span><strong>05</strong><span>CYBER SKILLS</span></div></div>
</section>
<section class="feature-grid">
  <article class="feature-card"><b>01 / INVESTIGATE</b><h3>Phishing Detective</h3><p>Inspect fictional emails, messages and links to find warning signs.</p></article>
  <article class="feature-card"><b>02 / PROTECT</b><h3>Password Master</h3><p>Build stronger authentication habits and secure a simulated vault.</p></article>
  <article class="feature-card"><b>03 / DEFEND</b><h3>Network Defender</h3><p>Make safe decisions to protect a fictional campus network.</p></article>
  <article class="feature-card"><b>04 / COMPETE</b><h3>Team Missions</h3><p>Join classmates in a shared training room and work toward a team score.</p></article>
</section>
<?php page_footer(); ?>
