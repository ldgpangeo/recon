create or replace view sponsorship_summary as
select s.name sponsor, n.name child,  r.reconid, r.itemid, r.civicrmid from r_recon r  
    left join r_search s on r.civicrmid = s.civicrmid and s.source = 'paypal'
    left join r_names n on r.itemid = n.itemid
    where r.is_active = 'Y';


create view recon_data as
select r.* , i.title as child, c.sort_name as sponsor 
from r_recon r
left join items i on r.itemid = i.itemid
left join cvcontacts c on c.id = r.civicrmid
where r.is_active = 'Y'



create or replace view cvcontacts as
select c.id, sort_name, first_name, middle_name, last_name, nick_name, email, p.phone, street_address, city, s.abbreviation as state,n.iso_code as nation, postal_code, c.modified_date
from 15983_tgc_wordpress_civicrm.civicrm_contact c
left join 15983_tgc_wordpress_civicrm.civicrm_email e on c.id = e.contact_id and e.is_primary = 1 and on_hold = 0 
left join 15983_tgc_wordpress_civicrm.civicrm_phone p on c.id = p.contact_id and p.is_primary = 1
left join 15983_tgc_wordpress_civicrm.civicrm_address a on c.id = a.contact_id and a.is_primary = 1
left join 15983_tgc_wordpress_civicrm.civicrm_state_province s on s.id = a.state_province_id
left join 15983_tgc_wordpress_civicrm.civicrm_country n on n.id = a.country_id
where  c.is_deceased = 0 and c.is_deleted = 0


create or replace view last_payments as
select r.* from r_payments r, (select max(datedone) as last_date ,reconid from r_payments where is_active = 'Y' group by reconid) m where m.last_date = r.datedone 
and m.reconid = r.reconid and r.is_active = 'Y' order by r.reconid ;

    
show payments for a donor
select datedone, source,amount,transactionid , s.*from r_payments p, sponsorship_summary s
where p.is_active = 'Y' and s.reconid = p.reconid and s.civicrmid = 155


to fix eggenberger/wellsheim crossover
select ac.* from civicrm_activity_contact ac, civicrm_activity a , civicrm_contribution c where c.contribution_recur_id = 302 and c.id = a.source_record_id 
and ac.activity_id = a.id
