# webservco/http-client-test

Tests for the `webservco/http-client` project.

Separate project in order to avoid adding unneeded dependencies to the library project.

---

## Running tests.

```shell
composer update

docker pull kennethreitz/httpbin
docker run -p 8080:80 kennethreitz/httpbin

composer test:dox
```
