ALTER TABLE event_store
    add column v_target_id varchar(36) GENERATED ALWAYS as (json_value(parameters, '$.id')) STORED,
    add column v_header_ip varchar(36) GENERATED ALWAYS as (json_value(headers, '$.ip')) STORED,
    add index (v_target_id, occurred_on),
    add index (v_header_ip, occurred_on);