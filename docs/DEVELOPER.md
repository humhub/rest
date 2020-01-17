# API development 

## Documentation

To completely adapt the API documentation after a change, the following steps are necessary.

### Swagger

The Swagger documentation is located in the folder `/docs/swagger`, you need to rebuild the html documentation 
at `/docs/html` which is based on the Swagger YAML files.

To create a HTML documentation you need to install the `redoc-cli` NPM package.

Build HTML documentation:

```
cd docs/swagger
./build-all.sh
```

### PostMan

Also add examples to the PostMan API request collection located in the folder: `/docs/postman`.


