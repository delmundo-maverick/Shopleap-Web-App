import test from 'node:test';
import assert from 'node:assert/strict';
import { existsSync, mkdirSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { delimiter, join, resolve, sep } from 'node:path';
import { spawnSync } from 'node:child_process';

const shell = process.platform === 'win32' ? 'C:/Program Files/Git/bin/sh.exe' : '/bin/sh';

function prepare(failingCommand = '') {
    const root = resolve('storage/framework');
    mkdirSync(root, { recursive: true });
    const directory = mkdtempSync(join(root, 'deployment-test-'));
    try {
        const log = join(directory, 'commands.log');
        writeFileSync(join(directory, 'php'), `#!/bin/sh
printf '%s\\n' "$*" >> "$DEPLOYMENT_TEST_LOG"
if [ -n "$DEPLOYMENT_TEST_FAIL" ]; then
    case "$*" in
        "artisan $DEPLOYMENT_TEST_FAIL "*) exit 17 ;;
    esac
fi
`, { mode: 0o755 });
        const result = spawnSync(shell, [resolve('docker/prepare-deploy.sh')], {
            encoding: 'utf8',
            env: {
                ...process.env,
                PATH: `${directory}${delimiter}${process.env.PATH}`,
                DEPLOYMENT_TEST_LOG: log.replaceAll('\\', '/'),
                DEPLOYMENT_TEST_FAIL: failingCommand,
            },
        });
        if (result.error) { throw result.error; }
        return { status: result.status, stderr: result.stderr, commands: existsSync(log) ? readFileSync(log, 'utf8').trim().split('\n') : [] };
    } finally {
        assert.ok(directory.startsWith(root + sep));
        rmSync(directory, { recursive: true, force: true });
    }
}

test('deployment migrates before caching and prepares successfully on repeated starts', { skip: !existsSync(shell) }, () => {
    for (let attempt = 0; attempt < 2; attempt++) {
        const result = prepare();
        assert.equal(result.status, 0, result.stderr);
        assert.deepEqual(result.commands, [
            'artisan config:clear --no-interaction',
            'artisan migrate --force --isolated=1 --no-interaction',
            'artisan config:cache --no-interaction',
            'artisan route:cache --no-interaction',
            'artisan view:cache --no-interaction',
        ]);
    }
});

for (const [command, count] of [['migrate', 2], ['config:cache', 3], ['route:cache', 4], ['view:cache', 5]]) {
    test(`deployment stops when ${command} fails`, { skip: !existsSync(shell) }, () => {
        const result = prepare(command);
        assert.equal(result.status, 17, result.stderr);
        assert.equal(result.commands.length, count);
    });
}
