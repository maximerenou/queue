Queue
======================================

[![Total Downloads](https://img.shields.io/packagist/dt/maximerenou/queue.svg)](https://packagist.org/packages/maximerenou/queue)
[![Latest Stable Version](https://img.shields.io/packagist/v/maximerenou/queue.svg)](https://packagist.org/packages/maximerenou/queue)

New features in this fork:

- You can push any job in a queue
- Jobs can ask to run one more time (try again)
- You can specify how many jobs you wish to keep in "successful", "error", "failed" and "retry" dynamic queues. It allows you to monitor last jobs without consuming too much disk space or memory.

Current implementations:

- Redis (see `example/bin/`)

### TODO

- Update tests
- Implements AWS SQS
- Implements MySQL

---

You may want to check the [original repository](https://github.com/javibravo/simpleue).