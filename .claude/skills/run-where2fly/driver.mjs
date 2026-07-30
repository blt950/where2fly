#!/usr/bin/env node
// Minimal chromium-cli-like REPL driver, built because chromium-cli isn't
// available in this environment. Reads newline-separated commands from
// stdin (or a script file passed as argv[2]) and drives a headless
// Chromium session against the running app.
//
// Commands:
//   nav <url>
//   wait-for text=<text> | wait-for <selector>   [timeoutMs]
//   click <selector>
//   fill <selector> <text...>
//   press <key>
//   screenshot [name]
//   screenshot-element <selector> [name]
//   console --errors
//   eval <js>
//   sleep <ms>
//
// Screenshots land in ./screenshots/<name-or-counter>.png (relative to CWD).

import { chromium } from 'playwright';
import readline from 'node:readline';
import fs from 'node:fs';
import path from 'node:path';

const screenshotDir = path.resolve(process.cwd(), 'screenshots');
fs.mkdirSync(screenshotDir, { recursive: true });

const browser = await chromium.launch({ args: ['--no-sandbox'] });
const context = await browser.newContext({ viewport: { width: 1400, height: 900 } });
const page = await context.newPage();

const consoleMessages = [];
page.on('console', (msg) => consoleMessages.push({ type: msg.type(), text: msg.text() }));
page.on('pageerror', (err) => consoleMessages.push({ type: 'pageerror', text: String(err) }));

let shotCounter = 0;

function parseSelector(arg) {
  if (arg.startsWith('text=')) {
    return { locator: page.getByText(arg.slice(5), { exact: false }) };
  }
  return { locator: page.locator(arg) };
}

async function runLine(lineRaw) {
  const line = lineRaw.trim();
  if (!line || line.startsWith('#')) return;
  const [cmd, ...rest] = line.split(' ');
  const argStr = rest.join(' ');

  try {
    switch (cmd) {
      case 'nav': {
        await page.goto(argStr, { waitUntil: 'domcontentloaded' });
        console.log(`[nav] ${argStr} -> ${page.url()}`);
        break;
      }
      case 'wait-for': {
        const [selArg, timeoutArg] = argStr.split(' ');
        const timeout = timeoutArg ? parseInt(timeoutArg, 10) : 15000;
        const { locator } = parseSelector(selArg);
        await locator.first().waitFor({ state: 'visible', timeout });
        console.log(`[wait-for] found: ${selArg}`);
        break;
      }
      case 'click': {
        const { locator } = parseSelector(argStr);
        await locator.first().click();
        console.log(`[click] ${argStr}`);
        break;
      }
      case 'fill': {
        const [selArg, ...text] = rest;
        const { locator } = parseSelector(selArg);
        await locator.first().fill(text.join(' '));
        console.log(`[fill] ${selArg} = ${text.join(' ')}`);
        break;
      }
      case 'press': {
        await page.keyboard.press(argStr);
        console.log(`[press] ${argStr}`);
        break;
      }
      case 'screenshot': {
        const name = argStr || `shot-${++shotCounter}`;
        const file = path.join(screenshotDir, `${name}.png`);
        await page.screenshot({ path: file, fullPage: true });
        fs.copyFileSync(file, path.join(screenshotDir, 'screenshot.png'));
        console.log(`[screenshot] ${file}`);
        break;
      }
      case 'screenshot-element': {
        const [selArg, name] = rest;
        const { locator } = parseSelector(selArg);
        const file = path.join(screenshotDir, `${name || `el-${++shotCounter}`}.png`);
        await locator.first().screenshot({ path: file });
        console.log(`[screenshot-element] ${file}`);
        break;
      }
      case 'console': {
        if (argStr === '--errors') {
          const errs = consoleMessages.filter((m) => m.type === 'error' || m.type === 'pageerror');
          console.log(`[console --errors] ${errs.length} error(s)`);
          errs.forEach((e) => console.log(`  ${e.type}: ${e.text}`));
        } else {
          consoleMessages.forEach((m) => console.log(`  ${m.type}: ${m.text}`));
        }
        break;
      }
      case 'eval': {
        const result = await page.evaluate(argStr);
        console.log(`[eval] ${JSON.stringify(result)}`);
        break;
      }
      case 'sleep': {
        await new Promise((r) => setTimeout(r, parseInt(argStr, 10)));
        break;
      }
      case 'quit':
      case 'exit':
        await browser.close();
        process.exit(0);
        break;
      default:
        console.log(`[?] unknown command: ${cmd}`);
    }
  } catch (err) {
    console.log(`[error] ${cmd}: ${err.message}`);
  }
}

const input = process.argv[2]
  ? fs.createReadStream(process.argv[2])
  : process.stdin;

const rl = readline.createInterface({ input, crlfDelay: Infinity });

for await (const line of rl) {
  await runLine(line);
}

await browser.close();
