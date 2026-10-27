-- ============================================================
-- GERENCIADOR DE EQUIPES — Dados Iniciais (Seeds)
-- sql/seeds.sql
-- Execute APÓS o schema.sql
-- ============================================================

USE gerenciador_equipes;

INSERT INTO ministerios (nome, descricao, cor_hex, icone) VALUES
  ('Louvor',   'Ministério de louvor e adoração',    '#0D9488', '🎵'),
  ('Mídia',    'Equipe de mídia, câmera e som',      '#F97316', '📷'),
  ('Kids',     'Ministério infantil',                '#EAB308', '🧒'),
  ('Recepção', 'Equipe de recepção e acolhimento',   '#16A34A', '🤝'),
  ('Oração',   'Grupo de intercessão e oração',      '#7C3AED', '🙏');

INSERT INTO habilidades (nome) VALUES
  ('Violão'),('Teclado'),('Bateria'),('Baixo'),('Vocal'),
  ('Câmera'),('Som'),('Designer'),('Transmissão'),
  ('Professora'),('Auxiliar'),('Recepcionista');

INSERT INTO disponibilidades (descricao) VALUES
  ('Dom. Manhã'),('Dom. Tarde'),('Dom. Noite'),
  ('Qua. Noite'),('Sex. Noite'),('Sáb. Tarde');

INSERT INTO supervisores (nome, email, telefone, ministerio_id, status, avatar_iniciais, avatar_cor) VALUES
  ('Ana Souza',     'ana.souza@email.com',   '(14) 99123-4567', 1, 'ativo',    'AS', '#0D9488'),
  ('Carlos Lima',   'carlos.lima@email.com', '(14) 98765-4321', 1, 'ativo',    'CL', '#F97316'),
  ('Maria Oliveira','maria.o@email.com',     '(14) 97654-3210', 2, 'pendente', 'MO', '#7C3AED'),
  ('Paulo Ferreira','paulo.f@email.com',     '(14) 96543-2109', 2, 'ativo',    'PF', '#16A34A'),
  ('Lúcia Mendes',  'lucia.m@email.com',     '(14) 95432-1098', 3, 'pendente', 'LM', '#EAB308'),
  ('Marcos Dias',   'marcos.d@email.com',    '(14) 94321-0987', 4, 'ativo',    'MD', '#EC4899');

INSERT INTO membros (nome, email, ministerio_id, supervisor_id, funcao, avatar_iniciais, avatar_cor) VALUES
  ('Ana Beatriz Costa',    'ana.b@email.com',      1, 1, 'Violão',        'A', '#0D9488'),
  ('Carlos Eduardo Melo',  'c.melo@email.com',     2, 4, 'Câmera',        'C', '#F97316'),
  ('Fernanda Lima Rocha',  'fernanda.r@email.com', 3, 5, 'Professora',    'F', '#7C3AED'),
  ('Roberto Santos Jr.',   'roberto.s@email.com',  4, 6, 'Recepcionista', 'R', '#16A34A'),
  ('Juliana Pereira Silva','juliana.s@email.com',  1, 1, 'Vocal',         'J', '#EAB308'),
  ('Marcos Rodrigues',     'marcos.r@email.com',   2, 4, 'Designer',      'M', '#EC4899');

INSERT INTO eventos (titulo, data_evento, horario, tipo) VALUES
  ('Culto de Louvor',   '2025-04-06', '09:00:00', 'louvor'),
  ('Culto de Quarta',   '2025-04-09', '19:30:00', 'culto'),
  ('Escala Especial',   '2025-04-13', '09:00:00', 'escala_especial'),
  ('Ministério Kids',   '2025-04-13', '09:00:00', 'kids'),
  ('Culto de Louvor',   '2025-04-20', '09:00:00', 'louvor'),
  ('Reunião de Equipe', '2025-04-27', '19:00:00', 'reuniao');

INSERT INTO evento_ministerios (evento_id, ministerio_id) VALUES
  (1,1),(1,2),(2,1),(2,2),(2,4),
  (3,1),(3,2),(3,3),(3,4),(4,3),
  (5,1),(5,2),(6,1),(6,2),(6,3),(6,4),(6,5);

INSERT INTO escalas (ministerio_id, supervisor_id, membro_id, data_escala, horario, funcao, status) VALUES
  (1,1,1,'2025-04-06','09:00:00','Violão',        'confirmado'),
  (1,2,5,'2025-04-06','19:30:00','Teclado',       'confirmado'),
  (2,4,2,'2025-04-09','19:30:00','Câmera 1',      'pendente'),
  (3,5,3,'2025-04-13','09:00:00','Professora',    'confirmado'),
  (4,6,4,'2025-04-13','08:30:00','Recepção',      'pendente'),
  (2,4,2,'2025-04-16','19:30:00','Som',           'ausente'),
  (1,1,5,'2025-04-20','09:00:00','Vocal',         'confirmado'),
  (3,5,3,'2025-04-20','09:00:00','Auxiliar',      'pendente');

INSERT INTO notificacoes (titulo, mensagem, tipo) VALUES
  ('Escala pendente',  '3 escalas aguardam confirmação.',              'alerta'),
  ('Novo membro',      'Marcos Rodrigues cadastrado no ministério Mídia.', 'sucesso'),
  ('Falta registrada', 'Carlos Eduardo marcou ausência na quarta.',    'alerta');
