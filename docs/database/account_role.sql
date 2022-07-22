CREATE TABLE IF NOT EXISTS account_role (
    account_type varchar(40) NOT NULL,
    account_id CHAR(36) NOT NULL,

    PRIMARY KEY account (account_type, account_id),

    role VARCHAR(10) NOT NULL
);
