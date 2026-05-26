CREATE TYPE course_topic AS ENUM (
    'inovacao',
    'tecnologia',
    'marketing',
    'empreendedorismo',
    'agro'
);

CREATE TYPE class_status AS ENUM (
    'disponivel',
    'encerrado'
);

CREATE TABLE courses (
    id          SERIAL PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    topic       course_topic NOT NULL,
    image_url   VARCHAR(500),
    created_at  TIMESTAMP DEFAULT NOW(),
    updated_at  TIMESTAMP DEFAULT NOW()
);

CREATE TABLE course_classes (
    id          SERIAL PRIMARY KEY,
    course_id   INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    slots       INTEGER NOT NULL CHECK (slots > 0),
    status      class_status NOT NULL DEFAULT 'disponivel',
    start_date  DATE NOT NULL,
    end_date    DATE NOT NULL,
    created_at  TIMESTAMP DEFAULT NOW(),
    updated_at  TIMESTAMP DEFAULT NOW(),
    CONSTRAINT  end_after_start CHECK (end_date >= start_date)
);

CREATE TABLE users (
    id         SERIAL PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    email      VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE enrollments (
    id               SERIAL PRIMARY KEY,
    user_id          INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    course_class_id  INTEGER NOT NULL REFERENCES course_classes(id) ON DELETE CASCADE,
    enrolled_at      TIMESTAMP DEFAULT NOW(),
    CONSTRAINT unique_enrollment UNIQUE (user_id, course_class_id)
);

CREATE INDEX idx_course_classes_course_id ON course_classes(course_id);
CREATE INDEX idx_course_classes_status    ON course_classes(status);
CREATE INDEX idx_enrollments_user_id      ON enrollments(user_id);
CREATE INDEX idx_enrollments_class_id     ON enrollments(course_class_id);
CREATE INDEX idx_courses_topic            ON courses(topic);
CREATE INDEX idx_courses_title            ON courses(title);
