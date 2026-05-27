INSERT INTO courses (title, description, topic, image_url) VALUES
('Marketing Digital', 'Curso completo de marketing digital para iniciantes e avançados.', 'marketing', 'https://example.com/marketing.jpg'),
('Inovação e Startups', 'Aprenda a criar e escalar startups inovadoras.', 'inovacao', 'https://example.com/inovacao.jpg'),
('Desenvolvimento Web', 'Do zero ao fullstack com tecnologias modernas.', 'tecnologia', 'https://example.com/tech.jpg'),
('Agronegócio Digital', 'Tecnologia e gestão para o agronegócio.', 'agro', 'https://example.com/agro.jpg'),
('Empreendedorismo Prático', 'Como abrir e gerir seu próprio negócio.', 'empreendedorismo', 'https://example.com/empreendedorismo.jpg');

INSERT INTO course_classes (course_id, title, description, slots, status, start_date, end_date) VALUES
(1, 'Turma A', 'Primeira turma do segundo semestre.', 30, 'disponivel', CURRENT_DATE, CURRENT_DATE + INTERVAL '90 days'),
(1, 'Turma B', 'Segunda turma do segundo semestre.', 25, 'disponivel', CURRENT_DATE + INTERVAL '91 days', CURRENT_DATE + INTERVAL '180 days'),
(2, 'Turma Única 2026', 'Turma anual de inovação.', 20, 'disponivel', CURRENT_DATE, CURRENT_DATE + INTERVAL '180 days'),
(3, 'Turma Intensiva', 'Curso intensivo de 3 meses.', 15, 'disponivel', CURRENT_DATE, CURRENT_DATE + INTERVAL '90 days'),
(3, 'Turma Encerrada', 'Turma já concluída.', 10, 'encerrado', CURRENT_DATE - INTERVAL '365 days', CURRENT_DATE - INTERVAL '180 days');

INSERT INTO users (name, email) VALUES
('João Silva', 'joao@example.com'),
('Maria Santos', 'maria@example.com'),
('Pedro Costa', 'pedro@example.com');
