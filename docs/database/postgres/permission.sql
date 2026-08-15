create table if not exists permission (
    id uuid not null primary key,

    version SMALLINT NOT NULL DEFAULT 1,

    identity_type varchar(40) not null,
    identity_id varchar(36) NOT NULL,

    flags_grant bool not null,
    flags_read bool not null,
    flags_create bool not null,
    flags_update bool not null,

    activity_last BIGINT NOT NULL
);
create index permission_identity on permission (identity_type, identity_id);
create index permission_flags on permission (flags_grant, flags_read, flags_create, flags_update);
create INDEX permission_activity_last on permission (activity_last);
