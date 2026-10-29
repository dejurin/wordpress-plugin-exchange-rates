const { execFileSync } = require('node:child_process');
const path = require('node:path');

const settingsPath = '/wp-admin/options-general.php?page=exchange-rates-general';

function prepareAdministrator(baseURL) {
  try {
    return JSON.parse(execFileSync('docker', [
      'compose', '--env-file', process.env.WP_TEST_ENV_FILE || 'wordpress/.env', 'run', '--rm', '--no-deps',
      'cli', 'eval-file', '/opt/tests/browser-admin.php', baseURL,
    ], { cwd: path.resolve(__dirname, '../../..'), timeout: 30000, stdio: ['ignore', 'pipe', 'pipe'] }).toString());
  } catch {
    // Do not expose captured stdout or credentials through a child-process error.
    throw new Error('Cannot prepare browser authentication. Initialize and select the matching disposable Compose project.');
  }
}

async function signIn(page, baseURL, credentials) {
  await page.route('**/*', route => new URL(route.request().url()).origin === new URL(baseURL).origin ? route.continue() : route.abort());
  try {
    await page.request.get('/wp-login.php');
    await page.request.post('/wp-login.php', { form: {
      log: credentials.username,
      pwd: credentials.password,
      testcookie: '1',
      redirect_to: new URL(settingsPath, baseURL).href,
    } });
  } catch {
    throw new Error('The disposable administrator could not sign in.');
  }
}

module.exports = { prepareAdministrator, signIn, settingsPath };
