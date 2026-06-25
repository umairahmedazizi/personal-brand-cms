<?php
// ai assistant settings
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../lib/settings.php';
require_login();

$groq = cfg()['groq'];
$keySet = trim($groq['api_key']) !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    set_setting('bot_enabled', isset($_POST['bot_enabled']) ? '1' : '0');
    set_setting('bot_persona', trim($_POST['bot_persona'] ?? ''));
    set_setting('bot_knowledge_1', trim($_POST['bot_knowledge_1'] ?? ''));
    set_setting('bot_knowledge_2', trim($_POST['bot_knowledge_2'] ?? ''));
    set_setting('bot_greeting', trim($_POST['bot_greeting'] ?? ''));
    flash('AI assistant settings saved.');
    redirect('/admin/chatbot.php');
}

$enabled  = get_setting('bot_enabled', '1') === '1';
$persona  = get_setting('bot_persona', '');
$kb1      = get_setting('bot_knowledge_1', '');
$kb2      = get_setting('bot_knowledge_2', '');
$greeting = get_setting('bot_greeting', '');

admin_header('AI Assistant');
?>
<?php if (!$keySet): ?>
<div class="flash flash--err">
  No Groq API key is set yet, so the chatbot is not live on the website. Add your key to
  <code>backend/config.php</code> (the <code>groq.api_key</code> value) to switch it on.
  You can still write the persona and knowledge below now.
</div>
<?php endif; ?>

<div class="panel" style="padding:24px;max-width:820px">
  <form method="post">
    <?= csrf_field() ?>

    <div class="field">
      <label><input type="checkbox" name="bot_enabled" value="1"<?= $enabled ? ' checked' : '' ?>>
        Show the chatbot on the website</label>
      <p class="field__hint">Master on/off switch. Also requires a Grok API key in config.php.</p>
    </div>

    <div class="field">
      <label>Greeting <span class="muted">(first message the bot shows)</span></label>
      <input type="text" name="bot_greeting" value="<?= e($greeting) ?>">
    </div>

    <div class="field">
      <label>Persona &amp; instructions</label>
      <textarea name="bot_persona" style="min-height:120px"><?= e($persona) ?></textarea>
      <p class="field__hint">Who the bot is and how it should behave. Keep it focused on Adrian's books &amp; ideas.</p>
    </div>

    <h3 style="margin:26px 0 4px;font-size:1rem;color:var(--ink-soft)">Knowledge base</h3>
    <p class="muted" style="margin-top:0;font-size:.85rem">
      The bot answers <strong>only</strong> from what you write here. It is sent with every question,
      so keep it focused — concise, well-structured notes work best and cost the least.
    </p>

    <div class="field">
      <label>Knowledge — File 1</label>
      <textarea name="bot_knowledge_1" style="min-height:200px"><?= e($kb1) ?></textarea>
    </div>

    <div class="field">
      <label>Knowledge — File 2</label>
      <textarea name="bot_knowledge_2" style="min-height:200px"><?= e($kb2) ?></textarea>
    </div>

    <button class="btn btn--primary">Save settings</button>
  </form>
</div>

<div class="panel" style="padding:20px 24px;max-width:820px;margin-top:22px">
  <h2 style="margin-top:0;font-size:1.05rem">Status &amp; rate guards</h2>
  <table>
    <tr><th style="width:220px">API key</th><td><?= $keySet ? '<span class="badge badge--green">set</span>' : '<span class="badge badge--draft">not set</span>' ?></td></tr>
    <tr><th>Provider</th><td>Groq <span class="muted">(console.groq.com — free tier available)</span></td></tr>
    <tr><th>Model</th><td><?= e($groq['model']) ?> <span class="muted">(verify current models at console.groq.com/docs/models)</span></td></tr>
    <tr><th>Rate limit</th><td><?= (int)$groq['rate_per_min'] ?>/min, <?= (int)$groq['rate_per_day'] ?>/day per visitor</td></tr>
    <tr><th>Max reply length</th><td><?= (int)$groq['max_tokens'] ?> tokens</td></tr>
  </table>
  <p class="muted" style="margin-bottom:0">Groq's free tier has its own rate/usage limits — these per-visitor guards help you stay within them.</p>
</div>
<?php admin_footer(); ?>
