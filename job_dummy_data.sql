-- เพิ่มข้อมูลบริษัท 5 แห่ง
INSERT INTO tb_company (com_name, com_detail, com_img) VALUES 
('TechDev Solutions', 'บริษัทพัฒนาซอฟต์แวร์และแอปพลิเคชันครบวงจร', 'company_1.jpg'),
('Innovative Marketing', 'เอเจนซี่การตลาดดิจิทัลรุ่นใหม่ไฟแรง', 'company_2.jpg'),
('DataWise Systems', 'ที่ปรึกษาด้านการวิเคราะห์ข้อมูลและ Big Data', 'company_3.jpg'),
('Green Energy Plus', 'ผู้นำด้านพลังงานสะอาดและเทคโนโลยีสิ่งแวดล้อม', 'company_4.jpg'),
('Creative Studio 99', 'สตูดิโอออกแบบกราฟิกและสื่อโฆษณา', 'company_5.jpg');

-- เพิ่มข้อมูลตำแหน่งงาน (ใช้ Subquery เพื่อดึง com_id)
INSERT INTO tb_company_detail (com_id, job_title, job_type, job_description, job_qualification, job_welfare, allowance, work_time, work_day) VALUES 
-- TechDev Solutions
((SELECT com_id FROM tb_company WHERE com_name='TechDev Solutions' LIMIT 1), 'Web Developer Intern', 'Programmer', 'พัฒนาเว็บไซต์ด้วย PHP และ React', 'เขียนโค้ดได้ อ่านโค้ดเป็น', 'มีขนมให้ทานฟรี', '300/วัน', '09:00 - 18:00', 'จันทร์ - ศุกร์'),

-- Innovative Marketing
((SELECT com_id FROM tb_company WHERE com_name='Innovative Marketing' LIMIT 1), 'Digital Marketing Intern', 'Marketing', 'ดูแลเพจและยิงโฆษณา Facebook', 'มีความคิดสร้างสรรค์ รู้ทันเทรนด์', 'Work from Home 1 วัน/สัปดาห์', '200/วัน', '10:00 - 19:00', 'จันทร์ - ศุกร์'),
((SELECT com_id FROM tb_company WHERE com_name='Innovative Marketing' LIMIT 1), 'Content Creator', 'Marketing', 'เขียนบทความและทำคอนเทนต์ลง YouTube', 'ตัดต่อวิดีโอเบื้องต้นได้', 'เลี้ยงข้าวกลางวัน', '250/วัน', '10:00 - 19:00', 'จันทร์ - ศุกร์'),

-- DataWise Systems
((SELECT com_id FROM tb_company WHERE com_name='DataWise Systems' LIMIT 1), 'Data Analyst Intern', 'Data Science', 'ช่วยวิเคราะห์ข้อมูลบริษัทลูกค้า', 'ใช้ Excel คล่อง, เขียน Python ได้จะดีมาก', 'มีพี่เลี้ยงสอนงานประกบ', '400/วัน', '09:00 - 18:00', 'จันทร์ - ศุกร์'),

-- Green Energy Plus
((SELECT com_id FROM tb_company WHERE com_name='Green Energy Plus' LIMIT 1), 'Electrical Engineer Intern', 'Engineer', 'ช่วยงานวิศวกรในการออกแบบระบบไฟฟ้า', 'กำลังศึกษาคณะวิศวกรรมศาสตร์ สาขาไฟฟ้า', 'ประกันอุบัติเหตุ', '350/วัน', '08:30 - 17:30', 'จันทร์ - ศุกร์'),

-- Creative Studio 99
((SELECT com_id FROM tb_company WHERE com_name='Creative Studio 99' LIMIT 1), 'Graphic Designer', 'Design', 'ออกแบบแบนเนอร์และสื่อสิ่งพิมพ์', 'ใช้ PS/AI คล่อง', 'บรรยากาศเป็นกันเอง', 'วันละ 300', '10:00 - 19:00', 'จันทร์ - ศุกร์');
