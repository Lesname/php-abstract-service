ALTER TABLE event_store
    ADD COLUMN v_target_id varchar(36)
        GENERATED ALWAYS AS ((parameters ->> 'id')) STORED,
    ADD COLUMN v_header_ip varchar(36)
        GENERATED ALWAYS AS ((headers ->> 'ip')) STORED;

CREATE INDEX event_store_target_occurred ON event_store (v_target_id, occurred_on);
CREATE INDEX event_store_ip_occurred ON event_store (v_header_ip, occurred_on);
