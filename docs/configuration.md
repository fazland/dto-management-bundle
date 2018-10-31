Fazland - DtoManagementBundle - Configuration
=============================================
Configuring DtoManagementBundle is really simple: just write down the `dto_management.yaml` file as the following:
```yaml
dto_management:
    namespaces:
        - 'App\Model'
```

That is: the only configuration is to tell the bundle where it has to look for DTOs interfaces and implementations.

Next step: [Nested dtos](./nested-dtos.md)
