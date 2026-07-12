-- =============================================================
-- Mousebook migration — M1-G (#37): DB-engine portability
-- =============================================================
-- Purpose:
--   Replace the MySQL-8-only collation `utf8mb4_0900_ai_ci` with
--   `utf8mb4_unicode_ci`, which exists on BOTH MySQL 8.x and
--   MariaDB 10.11+/11.x.
--
-- Why existing MySQL installs should apply this:
--   The running database keeps working either way. BUT the collation is
--   baked into (a) the two InnoDB reservation tables and (b) the stored
--   metadata of all 13 views. A `mysqldump` of an un-migrated MySQL
--   database re-emits `utf8mb4_0900_ai_ci`, and that dump CANNOT be
--   restored onto MariaDB — it aborts with:
--       ERROR 1273 (HY000): Unknown collation: 'utf8mb4_0900_ai_ci'
--   ...leaving a half-built database (tables, but no views/procedures).
--   Applying this migration makes your existing colony DB dump-portable.
--
-- Safety:
--   Idempotent — safe to re-run. No data is modified; only collation
--   metadata and view definitions are rewritten. Views are recreated
--   from the definitions shipped in mousebook_install_schema.sql.
--
-- Apply to: each COLONY (animalbook) database.
--   The userbook schema contains no utf8mb4_0900_ai_ci and needs nothing.
--
-- Usage:
--   mysql -u <admin> -p <colony_db> < migration_m1g_collation_portability.sql
--   (or: mariadb -u <admin> -p <colony_db> < ...)
--
-- Deploy ordering: apply this BEFORE/ALONGSIDE deploying the updated
--   mousebook_install_schema.sql. No PHP changes accompany it.
-- =============================================================

-- -------------------------------------------------------------
-- 1. Reservation tables (the only two InnoDB / utf8mb4 tables)
-- -------------------------------------------------------------
ALTER TABLE `reservations_animals`
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `reservations_cages`
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. Views — recreated so their stored collation_connection no
--    longer carries the MySQL-8-only collation.
--    Definitions are byte-identical to mousebook_install_schema.sql.
-- -------------------------------------------------------------

--
-- View: `view_activeanimals`
--

/*!50001 DROP VIEW IF EXISTS `view_activeanimals`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_activeanimals` AS select `y`.`cageno` AS `cageno`,`y`.`cagetype` AS `cagetype`,`y`.`lineassignment` AS `lineassignment`,`y`.`line` AS `line`,`y`.`idno` AS `idno`,`y`.`sex` AS `sex`,`y`.`eartag` AS `eartag`,`y`.`dob` AS `dob`,`y`.`genorxn` AS `genorxn`,`y`.`genotype` AS `genotype`,`conversion_geno`.`genoshort` AS `genoshort`,`y`.`matingcage` AS `matingcage`,`y`.`cagelocation` AS `location` from (`view_activeanimals_sub2` `y` left join `conversion_geno` on(((`y`.`genorxn` = convert(`conversion_geno`.`allelegroupscombo` using utf8mb3)) and (`y`.`genotype` = convert(`conversion_geno`.`genotype` using utf8mb3))))) order by `y`.`lineassignment`,field(`y`.`cagetype`,'holding','rearrange','experimental','mating','litter','sac'),`y`.`cageno`,`y`.`line`,`y`.`idno` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- View: `view_activeanimals_sub1`
--

/*!50001 DROP VIEW IF EXISTS `view_activeanimals_sub1`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_activeanimals_sub1` AS select `table_animals`.`animalautono` AS `animalautono`,`table_animals`.`line` AS `line`,`table_animals`.`idno` AS `idno`,`table_animals`.`dob` AS `dob`,`table_animals`.`dod` AS `dod`,`table_animals`.`sex` AS `sex`,`table_animals`.`eartag` AS `eartag`,`table_animals`.`matingcage` AS `matingcage`,`table_animals`.`currentcage` AS `currentcage`,`table_genotypes`.`allelegroup` AS `allelegroup`,`table_genotypes`.`allele` AS `allele` from (`table_animals` join `table_genotypes` on((`table_animals`.`animalautono` = `table_genotypes`.`animalautono`))) order by `table_animals`.`animalautono`,`table_genotypes`.`allelegroup` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- View: `view_activeanimals_sub2`
--

/*!50001 DROP VIEW IF EXISTS `view_activeanimals_sub2`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_activeanimals_sub2` AS select `table_cages`.`cageno` AS `cageno`,`table_cages`.`cagetype` AS `cagetype`,`table_cages`.`cagelocation_room` AS `cagelocation`,`table_cages`.`lineassignment` AS `lineassignment`,`table_cages`.`cageid` AS `cageid`,`x`.`line` AS `line`,`x`.`idno` AS `idno`,`x`.`sex` AS `sex`,`x`.`eartag` AS `eartag`,`x`.`dob` AS `dob`,group_concat(`x`.`allelegroup` order by `x`.`allelegroup` ASC separator '; ') AS `genorxn`,group_concat(`x`.`allele` order by `x`.`allelegroup` ASC separator '; ') AS `genotype`,`x`.`matingcage` AS `matingcage` from (`view_activeanimals_sub1` `x` join `table_cages` on((`x`.`currentcage` = `table_cages`.`cageid`))) where ((`x`.`dod` is null) and (`x`.`dob` is not null)) group by `x`.`line`,`x`.`idno`,`x`.`sex`,`x`.`eartag`,`x`.`dob`,`x`.`matingcage`,`table_cages`.`cageno`,`table_cages`.`cagetype`,`table_cages`.`cagelocation_room`,`table_cages`.`lineassignment`,`table_cages`.`cageid` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- View: `view_cagestatus`
--

/*!50001 DROP VIEW IF EXISTS `view_cagestatus`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_cagestatus` AS select `activcages`.`lineassignment` AS `lineassignment`,sum(if((`activcages`.`cagetype` = 'Mating'),1,0)) AS `mating count`,sum(if(((`activcages`.`cagetype` <> 'Mating') and (`activcages`.`cagetype` <> 'Litter')),1,0)) AS `holding Total`,sum(if((`activcages`.`cagetype` = 'Litter'),1,0)) AS `litter count`,sum(if(((`activcages`.`cagetype` <> 'Mating') and (`activcages`.`cagetype` <> 'Litter') and (`activcages`.`agemonth` <= 1)),1,0)) AS `holding count 1Mo`,sum(if(((`activcages`.`cagetype` <> 'Mating') and (`activcages`.`cagetype` <> 'Litter') and (`activcages`.`agemonth` = 2)),1,0)) AS `holding count 2Mo`,sum(if(((`activcages`.`cagetype` <> 'Mating') and (`activcages`.`cagetype` <> 'Litter') and (`activcages`.`agemonth` = 3)),1,0)) AS `holding count 3Mo`,sum(if(((`activcages`.`cagetype` <> 'Mating') and (`activcages`.`cagetype` <> 'Litter') and (`activcages`.`agemonth` = 4)),1,0)) AS `holding count 4Mo`,sum(if(((`activcages`.`cagetype` <> 'Mating') and (`activcages`.`cagetype` <> 'Litter') and (`activcages`.`agemonth` = 5)),1,0)) AS `holding count 5Mo`,sum(if(((`activcages`.`cagetype` <> 'Mating') and (`activcages`.`cagetype` <> 'Litter') and (`activcages`.`agemonth` = 6)),1,0)) AS `holding count 6Mo`,sum(if(((`activcages`.`cagetype` <> 'Mating') and (`activcages`.`cagetype` <> 'Litter') and (`activcages`.`agemonth` > 6)),1,0)) AS `holding count >6Mo` from `view_cagestatus_sub1` `activcages` group by `activcages`.`lineassignment` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- View: `view_cagestatus_sub1`
--

/*!50001 DROP VIEW IF EXISTS `view_cagestatus_sub1`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_cagestatus_sub1` AS select round((avg((to_days(curdate()) - to_days(`table_animals`.`dob`))) / 28),0) AS `agemonth`,`table_animals`.`currentcage` AS `currentcage`,`table_cages`.`cagetype` AS `cagetype`,`table_cages`.`lineassignment` AS `lineassignment` from (`table_animals` join `table_cages` on((`table_cages`.`cageid` = `table_animals`.`currentcage`))) where ((`table_animals`.`dob` is not null) and (`table_animals`.`dod` is null)) group by `table_cages`.`lineassignment`,`table_cages`.`cagetype`,`table_animals`.`currentcage` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- View: `view_goodanimals`
--

/*!50001 DROP VIEW IF EXISTS `view_goodanimals`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_goodanimals` AS select `table_genotypes`.`allele` AS `allele`,`table_genotypes`.`allelegroup` AS `allelegroup`,`table_animals`.`line` AS `line`,`table_animals`.`idno` AS `idno`,`table_animals`.`animalautono` AS `animalautono`,`table_animals`.`dob` AS `dob`,`table_animals`.`dod` AS `dod`,`table_animals`.`sex` AS `sex`,left(`table_animals`.`currentcage`,1) AS `cagetype`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele1`),`good_genos`.`geno1`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele2`),`good_genos`.`geno2`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele3`),`good_genos`.`geno3`,''))) AS `goodgeno`,`good_genos`.`alleles_needed` AS `numalleles`,(to_days(curdate()) - to_days(`table_animals`.`dob`)) AS `curagedays`,floor(((to_days(curdate()) - to_days(`table_animals`.`dob`)) / 30)) AS `curagemo`,if(((to_days(curdate()) - to_days(`table_animals`.`dob`)) > 120),'121orMore','120orLess') AS `curagegrp` from ((`table_animals` join `table_genotypes` on((`table_animals`.`animalautono` = `table_genotypes`.`animalautono`))) join `good_genos` on((`table_animals`.`line` = `good_genos`.`line`))) where (`table_animals`.`dod` is null) having ((`goodgeno` = `table_genotypes`.`allele`) and ((`cagetype` = 'H') or (`cagetype` = 'L'))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- View: `view_goodanimals_all`
--

/*!50001 DROP VIEW IF EXISTS `view_goodanimals_all`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_goodanimals_all` AS select `table_genotypes`.`allele` AS `allele`,`table_genotypes`.`allelegroup` AS `allelegroup`,`table_animals`.`line` AS `line`,`table_animals`.`idno` AS `idno`,`table_animals`.`animalautono` AS `animalautono`,`table_animals`.`dob` AS `dob`,`table_animals`.`dod` AS `dod`,`table_animals`.`sex` AS `sex`,left(`table_animals`.`currentcage`,1) AS `cagetype`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele1`),`good_genos`.`geno1`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele2`),`good_genos`.`geno2`,if((`table_genotypes`.`allelegroup` = `good_genos`.`allele3`),`good_genos`.`geno3`,''))) AS `goodgeno`,`good_genos`.`alleles_needed` AS `numalleles`,(to_days(curdate()) - to_days(`table_animals`.`dob`)) AS `curagedays`,floor(((to_days(curdate()) - to_days(`table_animals`.`dob`)) / 30)) AS `curagemo`,if(((to_days(curdate()) - to_days(`table_animals`.`dob`)) > 120),'121orMore','120orLess') AS `curagegrp` from ((`table_animals` join `table_genotypes` on((`table_animals`.`animalautono` = `table_genotypes`.`animalautono`))) join `good_genos` on((`table_animals`.`line` = `good_genos`.`line`))) having (`goodgeno` = `table_genotypes`.`allele`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- View: `view_goodanimals_filtered`
--

/*!50001 DROP VIEW IF EXISTS `view_goodanimals_filtered`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_goodanimals_filtered` AS select `v`.`line` AS `line`,`v`.`idno` AS `idno`,`v`.`animalautono` AS `animalautono`,`v`.`numalleles` AS `numalleles`,`v`.`curagegrp` AS `curagegrp`,`v`.`curagedays` AS `curagedays`,`v`.`curagemo` AS `curagemo` from `view_goodanimals` `v` group by `v`.`line`,`v`.`idno`,`v`.`animalautono`,`v`.`numalleles`,`v`.`curagegrp`,`v`.`curagedays`,`v`.`curagemo` having (count(0) = `v`.`numalleles`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- View: `view_goodanimals_filtered_all`
--

/*!50001 DROP VIEW IF EXISTS `view_goodanimals_filtered_all`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_goodanimals_filtered_all` AS select `v`.`line` AS `line`,`v`.`idno` AS `idno`,`v`.`animalautono` AS `animalautono`,`v`.`numalleles` AS `numalleles`,`v`.`curagegrp` AS `curagegrp`,`v`.`curagedays` AS `curagedays`,`v`.`curagemo` AS `curagemo` from `view_goodanimals_all` `v` group by `v`.`line`,`v`.`idno`,`v`.`animalautono`,`v`.`numalleles`,`v`.`curagegrp`,`v`.`curagedays`,`v`.`curagemo` having (count(0) = `v`.`numalleles`) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- View: `view_linestatus`
--

/*!50001 DROP VIEW IF EXISTS `view_linestatus`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_linestatus` AS select `table_lines`.`line` AS `line`,sum(if((`view_goodanimals_filtered`.`curagemo` <= 3),1,0)) AS `animals_0-3mo`,sum(if(((`view_goodanimals_filtered`.`curagemo` > 3) and (`view_goodanimals_filtered`.`curagemo` <= 6)),1,0)) AS `animals_4-6mo`,sum(if((`view_goodanimals_filtered`.`curagemo` > 6),1,0)) AS `animals_7+`,`view_matingcount`.`matings_0-3mo` AS `matings_0-3mo`,`view_matingcount`.`matings_4+mo` AS `matings_4+mo` from ((`table_lines` left join `view_goodanimals_filtered` on((`table_lines`.`line` = `view_goodanimals_filtered`.`line`))) left join `view_matingcount` on((`table_lines`.`line` = convert(`view_matingcount`.`lineassignment` using utf8mb3)))) group by `table_lines`.`line`,`view_matingcount`.`matings_0-3mo`,`view_matingcount`.`matings_4+mo` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- View: `view_matingcount`
--

/*!50001 DROP VIEW IF EXISTS `view_matingcount`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_matingcount` AS select `view_matingstatus`.`lineassignment` AS `lineassignment`,sum(if((`view_matingstatus`.`MatingAgeMos` <= 3),1,0)) AS `matings_0-3mo`,sum(if((`view_matingstatus`.`MatingAgeMos` > 3),1,0)) AS `matings_4+mo` from `view_matingstatus` group by `view_matingstatus`.`lineassignment` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- View: `view_matingstatus`
--

/*!50001 DROP VIEW IF EXISTS `view_matingstatus`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_matingstatus` AS select `table_cages`.`lineassignment` AS `lineassignment`,`table_cages`.`cageid` AS `cageid`,(to_days(curdate()) - to_days(`table_cages`.`setupdate`)) AS `MatingAgeDays`,floor(((to_days(curdate()) - to_days(`table_cages`.`setupdate`)) / 30)) AS `MatingAgeMos`,count(distinct `table_pups`.`idno`) AS `pupsmade`,count(distinct `view_goodanimals_filtered_all`.`idno`) AS `goodpupsmade`,count(distinct `table_pups`.`dob`) AS `Litters`,count(distinct `table_deadpups`.`dob`) AS `Dead Litters`,max(`table_pups`.`dob`) AS `LastLitterDOB`,max(`table_deadpups`.`dob`) AS `LastDeadLitterDOB` from (((`table_cages` left join `table_animals` on((`table_cages`.`cageid` = `table_animals`.`currentcage`))) left join (`table_animals` `table_pups` left join `view_goodanimals_filtered_all` on((`table_pups`.`animalautono` = `view_goodanimals_filtered_all`.`animalautono`))) on((`table_cages`.`cageid` = `table_pups`.`matingcage`))) left join `table_deadpups` on((`table_cages`.`cageid` = `table_deadpups`.`cageid`))) where ((`table_cages`.`cagetype` = 'Mating') and (`table_animals`.`sex` = 'F') and (`table_animals`.`dod` is null)) group by `table_cages`.`cageid` order by `table_cages`.`lineassignment`,`table_cages`.`cageno` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- View: `view_unkgenos`
--

/*!50001 DROP VIEW IF EXISTS `view_unkgenos`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY INVOKER */
/*!50001 VIEW `view_unkgenos` AS select `table_genotypes`.`allele` AS `allele`,`table_genotypes`.`allelegroup` AS `allelegroup`,`table_animals`.`line` AS `line`,`table_animals`.`idno` AS `idno`,`table_animals`.`dob` AS `dob`,`table_animals`.`dod` AS `dod` from (`table_genotypes` join `table_animals` on((`table_genotypes`.`animalautono` = `table_animals`.`animalautono`))) where ((`table_genotypes`.`allele` = 'unk') and (`table_animals`.`dod` is null)) order by `table_genotypes`.`allelegroup`,`table_animals`.`line`,cast(`table_animals`.`idno` as unsigned),`table_animals`.`idno` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;


-- -------------------------------------------------------------
-- 3. Verification (should return ZERO rows on success)
-- -------------------------------------------------------------
-- SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES
--   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_COLLATION LIKE '%0900%';
-- SELECT TABLE_NAME FROM information_schema.VIEWS
--   WHERE TABLE_SCHEMA = DATABASE() AND COLLATION_CONNECTION LIKE '%0900%';
-- SELECT ROUTINE_NAME FROM information_schema.ROUTINES
--   WHERE ROUTINE_SCHEMA = DATABASE() AND COLLATION_CONNECTION LIKE '%0900%';
--
-- NOTE: the 10 shipped procedures store `utf8mb3_general_ci` (portable on both
-- engines) and therefore need no rewrite. The ROUTINES check above is included
-- only to catch routines created outside the shipped schema, which would have
-- inherited the MySQL 8 server default collation.
