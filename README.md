# webservco/http-client-test

Tests for the `webservco/http-client` project.

Separate project in order to avoid adding unneeded dependencies to the library project.

---

## Running tests.

```shell
ddev restart 

ddev composer update

# problem: not able to run in ddev
docker pull kennethreitz/httpbin
docker run -p 8080:80 kennethreitz/httpbin

composer test:dox
```

---

## TODO

- [ ] Solve: `testManyReleasesUsingMultiWithRateLimiting`;
- [ ] Enable back all tests;
- [ ] Move Discogs tests to an Ogger Club project;
