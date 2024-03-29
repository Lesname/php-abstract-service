create table if not exists permission (
    id char(36) not null primary key,

    version SMALLINT UNSIGNED NOT NULL DEFAULT 1,

    identity_type varchar(40) not null,
    identity_id varchar(36) NOT NULL,

    index identity (identity_type, identity_id),

    flags_grant bool not null,
    flags_read bool not null,
    flags_write bool not null,

    index flags (flags_grant, flags_read, flags_write),

    activity_last BIGINT UNSIGNED NOT NULL,
    INDEX activity_last (activity_last)
);
