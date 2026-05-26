INSERT INTO courses (title, description, topic, image_url) VALUES
('Marketing Digital', 'Curso completo de marketing digital para iniciantes e avançados.', 'marketing', 'https://example.com/marketing.jpg'),
('Inovação e Startups', 'Aprenda a criar e escalar startups inovadoras.', 'inovacao', 'https://example.com/inovacao.jpg'),
('Desenvolvimento Web', 'Do zero ao fullstack com tecnologias modernas.', 'tecnologia', 'https://example.com/tech.jpg'),
('Agronegócio Digital', 'Tecnologia e gestão para o agronegócio.', 'agro', 'https://example.com/agro.jpg'),
('Empreendedorismo Prático', 'Como abrir e gerir seu próprio negócio.', 'empreendedorismo', 'https://example.com/empreendedorismo.jpg');

INSERT INTO course_classes (course_id, title, description, slots, status, start_date, end_date) VALUES
(1, 'Turma A - Julho 2026', 'Primeira turma do segundo semestre.', 30, 'disponivel', '2026-07-01', '2026-09-30'),
(1, 'Turma B - Outubro 2026', 'Segunda turma do segundo semestre.', 25, 'disponivel', '2026-10-01', '2026-12-15'),
(2, 'Turma Única 2026', 'Turma anual de inovação.', 20, 'disponivel', '2026-06-01', '2026-11-30'),
(3, 'Turma Intensiva', 'Curso intensivo de 3 meses.', 15, 'disponivel', '2026-07-15', '2026-10-15'),
(3, 'Turma Encerrada', 'Turma já concluída.', 10, 'encerrado', '2025-01-01', '2025-06-30');

INSERT INTO users (name, email) VALUES
('João Silva', 'joao@example.com'),
('Maria Santos', 'maria@example.com'),
('Pedro Costa', 'pedro@example.com');
