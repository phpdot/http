# Vendored PSR-7 conformance base classes

These files are vendored verbatim from
[php-http/psr7-integration-tests](https://github.com/php-http/psr7-integration-tests)
(v1.5.1, MIT, © PHP HTTP Team), namespace `Http\Psr7Test` preserved.

They are kept in-tree rather than pulled as a Composer dependency because the
upstream package caps at PHPUnit ≤ 12.4, while this monorepo runs PHPUnit 13.
The base classes themselves use only modern PHPUnit APIs and run unmodified on
PHPUnit 13. `tests/Conformance/*` extends these to drive PHPdot\Http through the
official PSR-7 compliance suite, building all inputs from PHPdot\Http's own
PSR-17 factory (see the `*_FACTORY` constants in `phpunit.xml`). No external
PSR-7 implementation is required.

See `LICENSE` for the upstream MIT license.
