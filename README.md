<p align="center">
    <a href="https://github.com/php-testo/testo"><img alt="TESTO"
         src="https://github.com/php-testo/.github/blob/1.x/resources/logo-full.svg?raw=true"
         style="width: 2in; display: block"
    /></a>
</p>

<p align="center">Output capturing & channel messaging plugin</p>

<div align="center">

[![Documentation](https://img.shields.io/badge/Documentation-blue?style=for-the-badge&logo=gitbook&logoColor=white)](https://php-testo.github.io)
[![Support on Boosty](https://img.shields.io/static/v1?style=for-the-badge&label=&message=Sponsorship&logo=Boosty&logoColor=white&color=%23F15F2C)](https://boosty.to/roxblnfk)

</div>

<br />

> [!IMPORTANT]
> ## 🪞 This is a read-only mirror.
>
> Active development of the Testo project lives in [**php-testo/testo**](https://github.com/php-testo/testo) under `plugin/messenger/`. This repository is **automatically synchronized** from there on every release.
>
> File issues and pull requests in the [main monorepo](https://github.com/php-testo/testo/issues), not here.

## About

Captures everything a test writes to the output buffer and routes it — together with anything other producers emit — through a channel-based messaging hub.

Each message carries a channel (e.g. `stdout`, `sql-log`), a severity level and its content. Messages are recorded per test (attached to the test's result), announced as events in real time, and can be produced from test code, middleware, or other plugins. The hub also exposes a PSR-3 logger per channel, so existing logging code feeds the same stream.

Output renderers (terminal, TeamCity) consume these messages to display per-test output, grouped and highlighted by channel.

## Install

```bash
composer require --dev testo/messenger
```

[![PHP](https://img.shields.io/packagist/php-v/testo/messenger.svg?style=flat-square&logo=php)](https://packagist.org/packages/testo/messenger)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/testo/messenger.svg?style=flat-square&logo=packagist)](https://packagist.org/packages/testo/messenger)
[![License](https://img.shields.io/packagist/l/testo/messenger.svg?style=flat-square)](https://github.com/php-testo/testo/blob/1.x/LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/testo/messenger.svg?style=flat-square)](https://packagist.org/packages/testo/messenger/stats)
