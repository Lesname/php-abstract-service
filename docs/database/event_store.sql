ALTER TABLE event_store
    add column v_target_id varchar(36) GENERATED ALWAYS as (JSON_UNQUOTE(JSON_EXTRACT(parameters, '$.id'))) STORED,
    add column v_header_ip varchar(36) GENERATED ALWAYS as (JSON_UNQUOTE(JSON_EXTRACT(headers, '$.ip'))) STORED;

ALTER TABLE event_store
    add index (v_target_id, occurred_on),
    add index (v_header_ip, occurred_on);