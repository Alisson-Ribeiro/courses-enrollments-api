# Courses API

API REST em PHP para gerenciamento de cursos, turmas e matrículas de alunos.

## Pré-requisitos

- [Docker](https://www.docker.com/) e Docker Compose v2+
- [Node.js](https://nodejs.org/) _(opcional — apenas para preview interativo da documentação OpenAPI)_

## Subindo o ambiente

```bash
docker compose up -d
docker compose exec app composer install
```

A API estará disponível em: **http://localhost:8080**

O Docker Compose aguarda o banco de dados estar pronto automaticamente antes de iniciar a aplicação. Confirme que tudo está no ar com o health check:

```bash
# Linux / macOS
curl http://localhost:8080/api/health

# Windows (PowerShell)
Invoke-WebRequest http://localhost:8080/api/health
```

### Carregar dados de exemplo (opcional)

```bash
# Linux / macOS
docker compose exec db psql -U api_user -d courses_api -f /dev/stdin < database/seeds/seed.sql

# Windows (PowerShell)
docker compose cp database/seeds/seed.sql db:/tmp/seed.sql
docker compose exec db psql -U api_user -d courses_api -f /tmp/seed.sql
```

## Acesso ao banco (ferramentas externas)

As portas do banco são expostas localmente para uso com pgAdmin, DBeaver ou psql:

| Ambiente | Host      | Porta | Banco              | Usuário    | Senha    |
|----------|-----------|-------|--------------------|------------|----------|
| App      | localhost | 5432  | courses_api        | api_user   | api_pass |
| Testes   | localhost | 5433  | courses_api_test   | api_user   | api_pass |

## Rodando os testes

Os testes usam um banco de dados separado (`db_test` na porta 5433), que sobe automaticamente com `docker compose up -d`.

- **Testes unitários** não requerem banco de dados
- **Testes de integração** requerem o container `db_test` rodando

```bash
# Todos os testes
docker compose exec app ./vendor/bin/phpunit

# Apenas testes unitários (sem banco)
docker compose exec app ./vendor/bin/phpunit --testsuite Unit

# Apenas testes de integração (requer db_test)
docker compose exec app ./vendor/bin/phpunit --testsuite Integration
```

## Limpando o ambiente

```bash
# Para os containers, mas preserva os dados do banco
docker compose down

# Remove os containers e apaga os dados do banco (reset completo)
docker compose down -v
```

## Documentação da API

O arquivo `docs/openapi.yaml` contém a especificação completa em OpenAPI 3.0.

Para visualizar interativamente _(requer [Node.js](https://nodejs.org/) instalado)_:

```bash
npx @redocly/cli preview-docs docs/openapi.yaml
```

## Endpoints

| Método   | Path                                        | Descrição                                |
|----------|---------------------------------------------|------------------------------------------|
| `GET`    | `/api/courses`                              | Listar cursos com turmas disponíveis     |
| `POST`   | `/api/courses`                              | Criar curso                              |
| `PUT`    | `/api/courses/{id}`                         | Atualizar curso                          |
| `DELETE` | `/api/courses/{id}`                         | Excluir curso                            |
| `POST`   | `/api/courses/{courseId}/classes`           | Criar turma                              |
| `PUT`    | `/api/courses/{courseId}/classes/{classId}` | Atualizar turma                          |
| `DELETE` | `/api/courses/{courseId}/classes/{classId}` | Excluir turma                            |
| `POST`   | `/api/users`                                | Criar usuário                            |
| `DELETE` | `/api/users/{id}`                           | Excluir usuário                          |
| `GET`    | `/api/users/{id}/enrollments`               | Listar matrículas do usuário             |
| `POST`   | `/api/enrollments`                          | Matricular usuário em turma              |
| `DELETE` | `/api/enrollments/{id}`                     | Cancelar matrícula                       |

### Filtros disponíveis em `GET /api/courses`

| Parâmetro | Tipo   | Descrição                                                       |
|-----------|--------|-----------------------------------------------------------------|
| `title`   | string | Busca parcial por título (case-insensitive)                     |
| `topic`   | string | Filtro exato por tema: `inovacao`, `tecnologia`, `marketing`, `empreendedorismo`, `agro` |

## Exemplos de uso (curl)

### Criar curso

```bash
curl -s -X POST http://localhost:8080/api/courses \
  -H "Content-Type: application/json" \
  -d '{"title":"Marketing Digital","description":"Aprenda marketing","topic":"marketing","image_url":"https://example.com/img.jpg"}'
```

### Criar turma

```bash
curl -s -X POST http://localhost:8080/api/courses/1/classes \
  -H "Content-Type: application/json" \
  -d '{"title":"Turma A","slots":30,"status":"disponivel","start_date":"2026-07-01","end_date":"2026-09-30"}'
```

### Listar cursos disponíveis com filtro

```bash
curl -s "http://localhost:8080/api/courses?topic=marketing"
curl -s "http://localhost:8080/api/courses?title=Digital"
```

### Criar usuário

```bash
curl -s -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"João Silva","email":"joao@example.com"}'
```

### Matricular usuário

```bash
curl -s -X POST http://localhost:8080/api/enrollments \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"course_class_id":1}'
```

### Listar matrículas do usuário

```bash
curl -s http://localhost:8080/api/users/1/enrollments
```

## Regras de negócio

- Não é possível se matricular em turmas com `status: encerrado`
- Não é possível se matricular em turmas fora do intervalo `start_date` / `end_date`
- Não é possível se matricular quando não há vagas disponíveis
- Um usuário não pode se matricular em mais de uma turma do mesmo curso
- Ao excluir um curso, todas as turmas são removidas (CASCADE)
- Ao excluir um usuário, todas as matrículas são removidas (CASCADE)

## Estrutura do projeto

```
courses-api/
├── docker/              # Configurações Docker
├── src/
│   ├── Config/          # Conexão com banco de dados
│   ├── Controllers/     # Camada HTTP
│   ├── Services/        # Regras de negócio
│   ├── Repositories/    # Acesso ao banco de dados
│   ├── Validators/      # Validação de entrada
│   ├── Exceptions/      # Exceções customizadas
│   ├── Helpers/         # Utilitários
│   └── Router.php       # Roteador HTTP
├── public/index.php     # Front controller
├── database/
│   ├── migrations/      # Schema SQL
│   └── seeds/           # Dados de exemplo
├── tests/
│   ├── Integration/     # Testes de integração (requerem banco)
│   └── Unit/            # Testes unitários
├── docs/openapi.yaml    # Documentação da API
└── docker-compose.yml
```
