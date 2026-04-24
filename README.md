# Formulario PHP com Banco de Dados (MySQL)

Projeto de cadastro de contatos com PHP + MySQL, rodando no XAMPP.

Repositorio: https://github.com/Amandaad/formulario-bancodedados-sa.git

## Visao Geral

Este projeto permite:

- cadastrar contatos com `nome`, `telefone` e `email`
- salvar dados no MySQL via PDO
- listar contatos salvos
- buscar contatos por nome ou email
- editar contatos
- excluir contatos

## Estrutura do Projeto

- `index.php`: formulario de cadastro
- `contatos.php`: listagem com busca, editar e excluir
- `editar.php`: tela de edicao de contato
- `imags/`: pasta reservada para imagens do projeto

## Requisitos

- XAMPP (Apache + MySQL)
- PHP (via XAMPP)
- Navegador
- Git (opcional, para versionamento)

## Banco de Dados

O projeto cria automaticamente (se nao existir):

- banco: `formulario`
- tabela: `contatos`

Estrutura principal da tabela:

- `id` (INT, PK, AUTO_INCREMENT)
- `nome` (VARCHAR 120)
- `telefone` (VARCHAR 30)
- `email` (VARCHAR 180)
- `criado_em` (TIMESTAMP)

## Como Rodar no XAMPP

1. Coloque a pasta do projeto em:
   - `C:\xampp\htdocs\formulario`
2. Abra o XAMPP Control Panel.
3. Inicie:
   - `Apache`
   - `MySQL`
4. Acesse no navegador:
   - `http://localhost/formulario/`

## Fluxo de Uso

1. Abrir `index.php` e cadastrar contato.
2. Clicar em `Ver contatos salvos`.
3. Na listagem (`contatos.php`):
   - buscar por nome/email
   - editar contato
   - excluir contato

## Validacoes Implementadas

- campos obrigatorios no cadastro e na edicao
- validacao de formato de email (`FILTER_VALIDATE_EMAIL`)
- queries preparadas (`prepare/execute`) para inserir, editar e excluir
- escapamento de saida com `htmlspecialchars`

## Principais URLs

- Cadastro: `http://localhost/formulario/`
- Listagem: `http://localhost/formulario/contatos.php`
- Edicao: `http://localhost/formulario/editar.php?id=1`

## Git (Comandos Principais)

### Clonar repositorio

```bash
git clone https://github.com/Amandaad/formulario-bancodedados-sa.git
cd formulario-bancodedados-sa
```

### Iniciar Git em projeto local (se ainda nao tiver)

```bash
git init
git branch -M main
git remote add origin https://github.com/Amandaad/formulario-bancodedados-sa.git
```

### Ver status e historico

```bash
git status
git log --oneline --graph --decorate -n 10
```

### Salvar alteracoes

```bash
git add .
git commit -m "feat: descricao da alteracao"
```

### Enviar para GitHub

```bash
git push -u origin main
```

### Atualizar com remoto

```bash
git pull origin main
```

### Criar branch para nova funcionalidade

```bash
git checkout -b feature/nova-funcionalidade
```

### Subir branch de feature

```bash
git push -u origin feature/nova-funcionalidade
```

## Boas Praticas de Commit

Exemplos:

- `feat: adiciona busca por nome e email`
- `fix: corrige validacao de email`
- `refactor: reorganiza conexao PDO`
- `docs: atualiza README`

## Solucao de Problemas

### Apache ou MySQL nao inicia

- verificar se as portas `80`, `443` e `3306` estao livres
- abrir o XAMPP como administrador
- conferir logs no botao `Logs` do XAMPP

### Erro de conexao com banco

- confirmar se o MySQL esta ativo no XAMPP
- validar credenciais no codigo:
  - host: `127.0.0.1`
  - usuario: `root`
  - senha: `''` (vazia por padrao no XAMPP)

### Erro de permissao Git (`dubious ownership`)

```bash
git config --global --add safe.directory C:/xampp/htdocs/formulario
```

## Licenca

Uso educacional e de estudo.
