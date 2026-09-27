# CuidarIdoso — Sistema de Gestão de Cuidados (versão PHP)

Esta é a versão em **PHP puro** (sem frameworks) do projeto original em React/TypeScript.
Mantém o mesmo visual (cores, tipografia Fraunces/Outfit, moldura de "celular") e as mesmas
funcionalidades, agora com um backend real: login com sessão, banco de dados persistente e
formulários que gravam de verdade.

## Requisitos

- PHP 8.0 ou superior
- Extensão `pdo_sqlite` habilitada (vem ativada por padrão na maioria das instalações)
- Extensão `mbstring` é recomendada, mas o projeto funciona sem ela (há *fallback* automático)

Nenhum servidor de banco de dados externo é necessário — o projeto usa **SQLite**, criando o
arquivo `data/cuidarbem.sqlite` automaticamente na primeira execução, já populado com dados de
exemplo (os mesmos do protótipo original).

## Como rodar localmente

Com o PHP instalado, dentro da pasta do projeto rode:

```bash
php -S localhost:8000
```

Depois acesse **http://localhost:8000** no navegador.

## Como rodar em um servidor Apache/Nginx com PHP (hospedagem comum)

Basta copiar todos os arquivos para a pasta pública do site (ex: `public_html`) e garantir que:

1. A pasta `data/` tenha permissão de escrita (o PHP precisa criar o arquivo do banco SQLite ali).
2. O PHP tenha a extensão `pdo_sqlite` ativa.

Não é necessário criar nenhum banco de dados manualmente nem rodar migrações — tudo é criado
automaticamente no primeiro acesso.

## Login de demonstração

```
E-mail: roberto.almeida@email.com
Senha:  12345678
```

Você também pode criar uma conta nova pela tela de cadastro.

## Estrutura do projeto

```
config/database.php        Conexão PDO + criação/seed automático das tabelas SQLite
includes/functions.php     Autenticação (sessão), helpers de formatação
includes/layout_top.php    Abre o HTML e a "moldura" do app
includes/layout_bottom.php Fecha o HTML
includes/header_component.php    Cabeçalho reutilizável das telas internas
includes/bottomnav_component.php Barra de navegação inferior
assets/css/style.css       Todo o estilo visual (paleta, tipografia, componentes)

login.php, register.php, logout.php     Autenticação
home.php                                Lista de pacientes
patient_registration.php                Cadastro de novo paciente
patient.php                             Detalhes do paciente
medications.php, tasks.php, diary.php   Telas por paciente (medicamentos, tarefas, diário)
search.php                              Busca e contratação de cuidadores
notifications.php                       Notificações
profile.php                             Perfil do usuário logado

actions/*.php               Processam os formulários (POST) e redirecionam de volta
```

## Banco de dados

Tabelas criadas automaticamente em `data/cuidarbem.sqlite`:

- `usuarios` — responsáveis e cuidadores cadastrados (login)
- `idosos` — pacientes cadastrados por cada responsável
- `medicamentos` — medicamentos de cada paciente
- `tarefas` — tarefas diárias de cuidado
- `diario` — entradas do diário de bordo (registro dos cuidadores)
- `notificacoes` — notificações do usuário
- `cuidadores` — cuidadores disponíveis para contratação
- `contratacoes` — solicitações de contratação enviadas

Cada paciente só pode ser acessado pelo responsável que o cadastrou (verificação feita em
`pacienteDoUsuarioOuFalha()`, em `includes/functions.php`).

## Diferenças em relação à versão React original

- Tudo roda no servidor (PHP), sem necessidade de JavaScript/build/Node.js.
- Os dados agora persistem de verdade em um banco SQLite, em vez de serem apenas um mock em
  memória que reiniciava a cada recarregamento de página.
- Autenticação real com senha criptografada (`password_hash`/`password_verify`) e sessão PHP.
- Cada ação (marcar tarefa, adicionar medicamento, etc.) é um formulário HTML que envia um
  POST para um script em `actions/`, que atualiza o banco e redireciona de volta — não há
  necessidade de JavaScript para o funcionamento básico.
