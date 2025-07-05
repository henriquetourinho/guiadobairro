-- Banco de Dados: `guia`
CREATE DATABASE IF NOT EXISTS guia;
USE guia;

-- Tabela: administradores
CREATE TABLE `administradores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_admin` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `nivel_acesso` enum('superadmin','moderador') NOT NULL DEFAULT 'moderador',
  `data_cadastro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: bairros_permitidos
CREATE TABLE `bairros_permitidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_bairro` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome_bairro` (`nome_bairro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: categorias
CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_categoria` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome_categoria` (`nome_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela: comerciantes
CREATE TABLE `comerciantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_responsavel` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `cnpj_cpf` varchar(18) DEFAULT NULL,
  `foto_capa` varchar(255) DEFAULT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `data_cadastro` timestamp NULL DEFAULT current_timestamp(),
  `status_conta` enum('ativo','inativo','pendente') DEFAULT 'pendente',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela: comercios_cnpj
CREATE TABLE `comercios_cnpj` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cnpj` varchar(14) NOT NULL,
  `razao_social` varchar(255) NOT NULL,
  `nome_fantasia` varchar(255) DEFAULT NULL,
  `data_abertura` date DEFAULT NULL,
  `comerciante_id` int(11) NOT NULL,
  `data_cadastro` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cnpj` (`cnpj`),
  KEY `comerciante_id` (`comerciante_id`),
  CONSTRAINT `comercios_cnpj_ibfk_1` FOREIGN KEY (`comerciante_id`) REFERENCES `comerciantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela: lojas
CREATE TABLE `lojas` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `comerciante_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `nome_loja` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `foto_capa` varchar(255) DEFAULT NULL,
  `Maps_link` varchar(500) DEFAULT NULL,
  `bairro_id` int(11) DEFAULT NULL,
  `status_publicacao` enum('publicado','pendente','rascunho','revisao','arquivado') DEFAULT 'pendente',
  `data_criacao` timestamp NULL DEFAULT current_timestamp(),
  `data_atualizacao` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `comerciante_id` (`comerciante_id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `fk_bairro_lojas` (`bairro_id`),
  CONSTRAINT `lojas_ibfk_1` FOREIGN KEY (`comerciante_id`) REFERENCES `comerciantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lojas_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  CONSTRAINT `fk_bairro_lojas` FOREIGN KEY (`bairro_id`) REFERENCES `bairros_permitidos` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela: contatos
CREATE TABLE `contatos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loja_id` int(11) UNSIGNED NOT NULL,
  `tipo_contato` enum('telefone','whatsapp','email','instagram','telegram') NOT NULL,
  `valor_contato` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `loja_id` (`loja_id`),
  CONSTRAINT `contatos_ibfk_1` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela: guia_config
CREATE TABLE `guia_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_guia` varchar(255) NOT NULL,
  `descricao_guia` text DEFAULT NULL,
  `link_politica_privacidade` varchar(255) DEFAULT NULL,
  `link_termos_uso` varchar(255) DEFAULT NULL,
  `email_contato` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabela: avaliacoes
CREATE TABLE `avaliacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loja_id` int(11) UNSIGNED NOT NULL,
  `nome_avaliador` varchar(255) NOT NULL,
  `nota` tinyint(4) NOT NULL COMMENT 'Nota de 1 a 5',
  `comentario` text DEFAULT NULL,
  `data_avaliacao` timestamp NULL DEFAULT current_timestamp(),
  `status_avaliacao` enum('aprovada','pendente','reprovada') DEFAULT 'pendente',
  PRIMARY KEY (`id`),
  KEY `loja_id` (`loja_id`),
  CONSTRAINT `avaliacoes_ibfk_1` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
