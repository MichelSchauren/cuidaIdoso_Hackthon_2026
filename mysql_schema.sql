-- ============================================================================
-- CuidaIdoso — Esquema MySQL (baseado em banco.sql, adaptado para MySQL 8+/MariaDB)
-- ============================================================================
-- Principais adaptações em relação ao banco.sql original:
--   - Adicionado ENGINE=InnoDB e CHARSET=utf8mb4 em todas as tabelas.
--   - SERIAL é mantido (no MySQL é um apelido para BIGINT UNSIGNED AUTO_INCREMENT).
--   - Os CHECK (...) foram mantidos como no original (suportados desde MySQL 8.0.16 / MariaDB 10.2).
-- A estrutura das tabelas, nomes de colunas e relacionamentos são EXATAMENTE
-- os definidos em banco.sql — nada foi renomeado ou removido.
-- ============================================================================

CREATE DATABASE bd_cuida_idoso;
USE bd_cuida_idoso;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS notificacao;
DROP TABLE IF EXISTS chamado;
DROP TABLE IF EXISTS diario;
DROP TABLE IF EXISTS tarefa;
DROP TABLE IF EXISTS historico_medicamento;
DROP TABLE IF EXISTS medicamento;
DROP TABLE IF EXISTS cuidador_idoso;
DROP TABLE IF EXISTS idoso;
DROP TABLE IF EXISTS cuidador;
DROP TABLE IF EXISTS usuario;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Tabela de Usuários (Centraliza os perfis de Responsável e Cuidador)
CREATE TABLE usuario (
    id SERIAL PRIMARY KEY,
    nome_completo VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    tipo_usuario VARCHAR(20) NOT NULL CHECK (tipo_usuario IN ('responsavel', 'cuidador')),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Perfil Específico do Cuidador (Apenas para usuários do tipo 'cuidador')
CREATE TABLE cuidador (
    id SERIAL PRIMARY KEY,
    usuario_id BIGINT UNSIGNED UNIQUE NOT NULL REFERENCES usuario(id) ON DELETE CASCADE,
    biografia TEXT,
    cidade VARCHAR(100) DEFAULT NULL,
    valor_hora DECIMAL(10, 2),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    esta_verificado BOOLEAN DEFAULT FALSE,
    especialidades TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cuidador_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabela de idoso / Pacientes (Cadastrados pelos Responsáveis)
CREATE TABLE idoso (
    id SERIAL PRIMARY KEY,
    responsavel_id BIGINT UNSIGNED NOT NULL,
    nome_completo VARCHAR(150) NOT NULL,
    data_nascimento DATE NOT NULL,
    condicoes_medicas TEXT,
    telefone_emergencia VARCHAR(20) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_idoso_responsavel FOREIGN KEY (responsavel_id) REFERENCES usuario(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Vinculo de Cuidadores Autorizados a Cuidar de um Idoso
CREATE TABLE cuidador_idoso (
    id SERIAL PRIMARY KEY,
    idoso_id BIGINT UNSIGNED NOT NULL,
    cuidador_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) DEFAULT 'ativo' CHECK (status IN ('ativo', 'inativo')),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(idoso_id, cuidador_id),
    CONSTRAINT fk_ci_idoso FOREIGN KEY (idoso_id) REFERENCES idoso(id) ON DELETE CASCADE,
    CONSTRAINT fk_ci_cuidador FOREIGN KEY (cuidador_id) REFERENCES usuario(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Cadastro de medicamento do Idoso
CREATE TABLE medicamento (
    id SERIAL PRIMARY KEY,
    idoso_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    dosagem VARCHAR(50) NOT NULL,
    intervalo_horas INT NOT NULL,
    instrucoes TEXT,
    ativo BOOLEAN DEFAULT TRUE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_medicamento_idoso FOREIGN KEY (idoso_id) REFERENCES idoso(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Histórico de Execução de medicamento
CREATE TABLE historico_medicamento (
    id SERIAL PRIMARY KEY,
    medicamento_id BIGINT UNSIGNED NOT NULL,
    cuidador_id BIGINT UNSIGNED,
    horario_previsto TIMESTAMP NOT NULL,
    horario_administrado TIMESTAMP NULL,
    status VARCHAR(20) DEFAULT 'pendente' CHECK (status IN ('pendente', 'administrado', 'pulado', 'atrasado')),
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_historico_medicamento FOREIGN KEY (medicamento_id) REFERENCES medicamento(id) ON DELETE CASCADE,
    CONSTRAINT fk_historico_cuidador FOREIGN KEY (cuidador_id) REFERENCES usuario(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. tarefa e Rotinas Diárias
CREATE TABLE tarefa (
    id SERIAL PRIMARY KEY,
    idoso_id BIGINT UNSIGNED NOT NULL,
    cuidador_id BIGINT UNSIGNED,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT,
    horario_previsto TIMESTAMP NOT NULL,
    status VARCHAR(20) DEFAULT 'pendente' CHECK (status IN ('pendente', 'concluida', 'cancelada')),
    concluida_em TIMESTAMP NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tarefa_idoso FOREIGN KEY (idoso_id) REFERENCES idoso(id) ON DELETE CASCADE,
    CONSTRAINT fk_tarefa_cuidador FOREIGN KEY (cuidador_id) REFERENCES usuario(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Diário de Bordo (Passagem de plantão e comunicações)
CREATE TABLE diario (
    id SERIAL PRIMARY KEY,
    idoso_id BIGINT UNSIGNED NOT NULL,
    cuidador_id BIGINT UNSIGNED NOT NULL,
    categoria VARCHAR(30) DEFAULT 'rotina' CHECK (categoria IN ('rotina', 'incidente', 'observacao', 'sinais_vitais')),
    mensagem TEXT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_diario_idoso FOREIGN KEY (idoso_id) REFERENCES idoso(id) ON DELETE CASCADE,
    CONSTRAINT fk_diario_cuidador FOREIGN KEY (cuidador_id) REFERENCES usuario(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Solicitações de Contratação (Pesquisa e aceite de cuidadores)
CREATE TABLE chamado (
    id SERIAL PRIMARY KEY,
    responsavel_id BIGINT UNSIGNED NOT NULL,
    cuidador_id BIGINT UNSIGNED NOT NULL,
    idoso_id BIGINT UNSIGNED NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    valor_acordado DECIMAL(10, 2),
    status VARCHAR(20) DEFAULT 'pendente' CHECK (status IN ('pendente', 'aceita', 'recusada', 'concluida', 'cancelada')),
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_chamado_responsavel FOREIGN KEY (responsavel_id) REFERENCES usuario(id) ON DELETE CASCADE,
    CONSTRAINT fk_chamado_cuidador FOREIGN KEY (cuidador_id) REFERENCES usuario(id) ON DELETE CASCADE,
    CONSTRAINT fk_chamado_idoso FOREIGN KEY (idoso_id) REFERENCES idoso(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Notificações do Sistema
CREATE TABLE notificacao (
    id SERIAL PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL,
    titulo VARCHAR(100) NOT NULL,
    mensagem TEXT NOT NULL,
    lida BOOLEAN DEFAULT FALSE,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notificacao_usuario FOREIGN KEY (usuario_id) REFERENCES usuario(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Dados de demonstração (mesmos personagens do seed original em SQLite)
-- Senha de todos os usuários de login: 12345678
-- Hash gerado com password_hash('12345678', PASSWORD_BCRYPT)
-- ============================================================================

INSERT INTO usuario (nome_completo, email, senha_hash, telefone, tipo_usuario) VALUES
('Roberto Almeida', 'roberto.almeida@email.com', '$2y$10$S05dQnCo5QMJ.4gRUpHPSOMHO2qj6lkVa960WnUXbe9JS9Wl7g3pi', '(11) 99234-5678', 'responsavel'),
('Ana Costa', 'ana.costa@email.com', '$2y$10$S05dQnCo5QMJ.4gRUpHPSOMHO2qj6lkVa960WnUXbe9JS9Wl7g3pi', '(11) 98111-2233', 'cuidador'),
('Beatriz Lima', 'beatriz.lima@email.com', '$2y$10$S05dQnCo5QMJ.4gRUpHPSOMHO2qj6lkVa960WnUXbe9JS9Wl7g3pi', '(11) 98222-3344', 'cuidador'),
('Carlos Santos', 'carlos.santos@email.com', '$2y$10$S05dQnCo5QMJ.4gRUpHPSOMHO2qj6lkVa960WnUXbe9JS9Wl7g3pi', '(11) 98333-4455', 'cuidador'),
('Fernanda Rocha', 'fernanda.rocha@email.com', '$2y$10$S05dQnCo5QMJ.4gRUpHPSOMHO2qj6lkVa960WnUXbe9JS9Wl7g3pi', '(11) 98444-5566', 'cuidador');

-- responsavel_id = 1 (Roberto Almeida)
INSERT INTO cuidador (usuario_id, biografia, valor_hora, esta_verificado, especialidades) VALUES
(2, 'Cuidadora profissional há 8 anos, com formação em gerontologia. Disponível para plantões diurnos e noturnos.', 25.00, TRUE, 'Hipertensão, Mobilidade reduzida'),
(3, 'Técnica de enfermagem com 12 anos de experiência em cuidados de idosos. Especializada em pacientes com demência.', 28.00, TRUE, 'Alzheimer, Cuidados paliativos'),
(4, 'Cuidador com 5 anos de experiência. Realizou curso de primeiros socorros e auxiliar de enfermagem.', 22.00, FALSE, 'Diabetes, Fisioterapia básica'),
(5, 'Enfermeira com pós-graduação em geriatria. Atende casos complexos com dedicação e empatia.', 30.00, TRUE, 'Parkinson, AVC, Demência');

INSERT INTO idoso (responsavel_id, nome_completo, data_nascimento, condicoes_medicas, telefone_emergencia) VALUES
(1, 'Maria Silva', '1947-03-12', 'Alzheimer em estágio inicial, Hipertensão arterial', '(11) 98765-4321'),
(1, 'José Pereira', '1942-09-05', 'Diabetes tipo 2, Artrose nos joelhos', '(11) 97654-3210');

-- Ana Costa (usuario_id 2) cuida da Maria Silva (idoso 1); Carlos Santos (usuario_id 4) cuida do José Pereira (idoso 2)
INSERT INTO cuidador_idoso (idoso_id, cuidador_id, status) VALUES
(1, 2, 'ativo'),
(2, 4, 'ativo');

INSERT INTO medicamento (idoso_id, nome, dosagem, intervalo_horas, instrucoes, ativo) VALUES
(1, 'Donepezila', '5 mg — 1 comprimido', 24, 'Tomar à noite, antes de dormir', TRUE),
(1, 'Losartana', '50 mg — 1 comprimido', 12, 'Tomar após o café da manhã e jantar', TRUE),
(1, 'Memantina', '10 mg — 1 comprimido', 12, 'Tomar com água, independente de refeições', TRUE),
(2, 'Metformina', '500 mg — 1 comprimido', 8, 'Tomar durante as refeições principais', TRUE),
(2, 'Diclofenaco', '50 mg — 1 comprimido', 12, 'Tomar com alimento. Apenas em caso de dor.', TRUE);

INSERT INTO historico_medicamento (medicamento_id, cuidador_id, horario_previsto, horario_administrado, status) VALUES
(1, 2, CONCAT(CURDATE(), ' 22:00:00'), NULL, 'pendente'),
(2, 2, CONCAT(CURDATE(), ' 08:00:00'), CONCAT(CURDATE(), ' 08:05:00'), 'administrado'),
(3, 2, CONCAT(CURDATE(), ' 07:30:00'), NULL, 'atrasado'),
(4, 4, CONCAT(CURDATE(), ' 12:00:00'), NULL, 'pendente'),
(5, 4, CONCAT(CURDATE(), ' 14:00:00'), NULL, 'pendente');

INSERT INTO tarefa (idoso_id, cuidador_id, titulo, descricao, horario_previsto, status, concluida_em) VALUES
(1, 2, 'Banho matinal', 'Auxiliar no banho com água morna. Verificar pele.', CONCAT(CURDATE(), ' 08:00:00'), 'concluida', CONCAT(CURDATE(), ' 08:15:00')),
(1, 2, 'Café da manhã', 'Preparar mingau de aveia com frutas. Verificar glicemia.', CONCAT(CURDATE(), ' 08:30:00'), 'concluida', CONCAT(CURDATE(), ' 08:50:00')),
(1, 2, 'Fisioterapia', 'Exercícios de mobilidade com bola e faixa elástica.', CONCAT(CURDATE(), ' 10:00:00'), 'pendente', NULL),
(1, 2, 'Almoço e medicação', 'Almoço leve. Administrar medicamentos conforme prescrição.', CONCAT(CURDATE(), ' 12:00:00'), 'pendente', NULL),
(1, 2, 'Caminhada no jardim', 'Passeio de 15 minutos. Manter ritmo tranquilo.', CONCAT(CURDATE(), ' 16:00:00'), 'pendente', NULL),
(2, 4, 'Verificação de glicemia', 'Medir glicemia em jejum. Registrar resultado.', CONCAT(CURDATE(), ' 07:00:00'), 'concluida', CONCAT(CURDATE(), ' 07:10:00')),
(2, 4, 'Exercícios para artrose', 'Exercícios leves de flexão dos joelhos, 10 min.', CONCAT(CURDATE(), ' 09:00:00'), 'pendente', NULL);

INSERT INTO diario (idoso_id, cuidador_id, categoria, mensagem, criado_em) VALUES
(1, 2, 'sinais_vitais', 'PA: 130/85 mmHg. FC: 72 bpm. Temperatura: 36,4°C. Saturação: 97%. Paciente bem disposta.', CONCAT(CURDATE(), ' 07:15:00')),
(1, 2, 'rotina', 'Café da manhã realizado sem intercorrências. Dona Maria comeu bem — aceitou o mingau todo. Humor ótimo hoje, conversou muito.', CONCAT(CURDATE(), ' 08:45:00')),
(1, 3, 'observacao', 'Notei leve inchaço no tornozelo direito. Recomendo avaliar com o médico. Mantive a perna elevada após o almoço.', CONCAT(CURDATE(), ' 13:20:00')),
(1, 2, 'incidente', 'Dona Maria ficou agitada por volta das 15h, não reconhecia o ambiente. Conversei com calma, passou em ~20 min. Registrado para avaliação médica.', CONCAT(CURDATE(), ' 15:35:00'));

INSERT INTO chamado (responsavel_id, cuidador_id, idoso_id, data_inicio, data_fim, valor_acordado, status) VALUES
(1, 3, 1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 8 DAY), 28.00, 'aceita');

INSERT INTO notificacao (usuario_id, titulo, mensagem, lida, criado_em) VALUES
(1, 'Medicamento atrasado', 'Memantina de Dona Maria Silva ainda não foi administrada. Prevista para 07:30.', FALSE, CONCAT(CURDATE(), ' 07:45:00')),
(1, 'Solicitação aceita', 'Beatriz Lima aceitou sua solicitação de cuidado para Dona Maria nos dias 16 a 20/08.', FALSE, CONCAT(CURDATE(), ' 09:10:00')),
(1, 'Nova entrada no diário', 'Ana Costa registrou uma observação importante sobre Dona Maria Silva.', FALSE, CONCAT(CURDATE(), ' 13:25:00')),
(1, 'Tarefa concluída', 'Carlos Santos marcou "Verificação de glicemia" de Sr. José como concluída.', TRUE, CONCAT(CURDATE(), ' 07:05:00'));
