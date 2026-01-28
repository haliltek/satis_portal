<?php
function getAgingSql($firmNr, $type) {
    // Pad firmNr to 3 digits (e.g. 525)
    $firm = str_pad($firmNr, 3, '0', STR_PAD_LEFT);
    $period = '01'; // Default period

    $emfline = "LG_{$firm}_{$period}_EMFLINE";
    $emuhacc = "LG_{$firm}_EMUHACC";

    // Common Temp Table Setup
    // Note: In PDO, temp tables like #tempresults might be session-scoped.
    // We wrap everything in one execution to be safe.
    
    $commonSetup = "
    SET NOCOUNT ON;
    IF OBJECT_ID('tempdb..#tempresults') IS NOT NULL DROP TABLE #tempresults;
    IF OBJECT_ID('tempdb..#tempcredits') IS NOT NULL DROP TABLE #tempcredits;
    IF OBJECT_ID('tempdb..#tempdebits') IS NOT NULL DROP TABLE #tempdebits;

    CREATE TABLE #tempresults (
        [RECID] [int] NULL,
        [SIGN] [smallint] NULL,
        [DEBIT] [float] NULL,
        [CREDIT] [float] NULL,
        [DATE_] [datetime] NULL,
        [LINEEXP] [varchar](251) NULL,
        [ACCOUNTCODE] [varchar](101) NULL,
        [INVOICENO] [varchar](17) NULL,
        [CLOSED] [smallint] NULL,
        [ACILIS] [float] NULL,
        [OCAK] [float] NULL,
        [SUBAT] [float] NULL,
        [MART] [float] NULL,
        [NISAN] [float] NULL,
        [MAYIS] [float] NULL,
        [HAZIRAN] [float] NULL,
        [TEMMUZ] [float] NULL,
        [AGUSTOS] [float] NULL,
        [EYLUL] [float] NULL,
        [EKIM] [float] NULL,
        [KASIM] [float] NULL,
        [ARALIK] [float] NULL,
        [ACCOUNTNAME] [varchar](200) NULL
    );
    ";

    if ($type === 'debit') {
        // --- BORÇ YAŞLANDIRMA (120) ---
        $logic = "
        -- Tahsilatlar Temp Tablo
        CREATE TABLE #tempcredits (
            [CREDIT] [float] NULL,
            [ACCOUNTCODE] [varchar](101) NULL,
            [REMAINING] [float] NULL
        );
        
        INSERT INTO #tempcredits
        SELECT SUM(CREDIT) as CREDIT, ACCOUNTCODE, SUM(CREDIT) as REMAINING
        FROM {$emfline}
        WHERE CANCELLED<>1 AND [SIGN]=1 AND ACCOUNTCODE LIKE '120.%'
        GROUP BY ACCOUNTCODE;

        DECLARE @sign smallint, @debit float, @credit float, @date datetime;
        DECLARE @lineexp varchar(251), @accountcode varchar(101), @invoiceno varchar(17);
        DECLARE @month smallint, @trcode smallint, @recid int, @accountname varchar(200);
        
        SET @recid = 0;

        DECLARE MY_CURSOR CURSOR LOCAL STATIC READ_ONLY FORWARD_ONLY FOR 
        SELECT el.[SIGN], el.DEBIT, el.CREDIT, el.DATE_, el.LINEEXP, el.ACCOUNTCODE, el.INVOICENO, el.MONTH_, ca.DEFINITION_, el.TRCODE
        FROM {$emfline} el
        LEFT JOIN {$emuhacc} ca ON ca.LOGICALREF = el.ACCOUNTREF
        WHERE el.CANCELLED<>1 
        AND el.ACCOUNTCODE LIKE '120.%'
        ORDER BY el.DATE_ ASC;

        OPEN MY_CURSOR;
        FETCH NEXT FROM MY_CURSOR INTO @sign, @debit, @credit, @date, @lineexp, @accountcode, @invoiceno, @month, @accountname, @trcode;

        WHILE @@FETCH_STATUS = 0
        BEGIN 
            SET @recid = @recid + 1;
            DECLARE @remaining FLOAT = 0;
            DECLARE @splitted smallint = 0;
            
            SELECT @remaining = ISNULL(REMAINING,0) FROM #tempcredits WHERE ACCOUNTCODE = @accountcode;
            
            IF @remaining = 0 OR @remaining IS NULL
            BEGIN
                INSERT INTO #tempresults VALUES(@recid, @sign, @debit, @credit, @date, @lineexp, @accountcode, @invoiceno, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, @accountname);
            END
            ELSE
            BEGIN
                IF NOT (@remaining - @debit < 0) AND @sign = 0
                BEGIN
                    UPDATE #tempcredits SET REMAINING = REMAINING - @debit WHERE ACCOUNTCODE = @accountcode;
                    INSERT INTO #tempresults VALUES(@recid, @sign, @debit, @credit, @date, @lineexp, @accountcode, @invoiceno, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, @accountname);
                END
                
                IF (@remaining - @debit < 0) AND @sign = 0 AND @remaining <> 0
                BEGIN
                    UPDATE #tempcredits SET REMAINING = 0 WHERE ACCOUNTCODE = @accountcode;
                    SET @splitted = 1;
                    INSERT INTO #tempresults VALUES(@recid, @sign, @remaining, @credit, @date, @lineexp, @accountcode, @invoiceno, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, @accountname);
                    SET @recid = @recid + 1;
                    INSERT INTO #tempresults VALUES(@recid, @sign, @debit - @remaining, @credit, @date, 'PARÇALANDI : ' + ISNULL(@lineexp,''), @accountcode, @invoiceno, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, @accountname);
                END
                
                IF @remaining = 0 AND @sign <> 1
                BEGIN
                    INSERT INTO #tempresults VALUES(@recid, @sign, @debit, @credit, @date, @lineexp, @accountcode, @invoiceno, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, @accountname);
                END
                
                IF @sign = 1
                BEGIN
                    INSERT INTO #tempresults VALUES(@recid, @sign, @debit, @credit, @date, @lineexp, @accountcode, @invoiceno, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, @accountname);
                END
            END
            
            IF ((@remaining - @debit < 0 AND @remaining <> 0) OR @remaining = 0) AND @sign = 0
            BEGIN
                UPDATE #tempresults
                SET 
                ACILIS = CASE WHEN @trcode = 1 THEN @debit - @remaining ELSE 0 END,
                OCAK = CASE WHEN @month = 1 AND @trcode <> 1 THEN @debit - @remaining ELSE 0 END,
                SUBAT = CASE WHEN @month = 2 THEN @debit - @remaining ELSE 0 END,
                MART = CASE WHEN @month = 3 THEN @debit - @remaining ELSE 0 END,
                NISAN = CASE WHEN @month = 4 THEN @debit - @remaining ELSE 0 END,
                MAYIS = CASE WHEN @month = 5 THEN @debit - @remaining ELSE 0 END,
                HAZIRAN = CASE WHEN @month = 6 THEN @debit - @remaining ELSE 0 END,
                TEMMUZ = CASE WHEN @month = 7 THEN @debit - @remaining ELSE 0 END,
                AGUSTOS = CASE WHEN @month = 8 THEN @debit - @remaining ELSE 0 END,
                EYLUL = CASE WHEN @month = 9 THEN @debit - @remaining ELSE 0 END,
                EKIM = CASE WHEN @month = 10 THEN @debit - @remaining ELSE 0 END,
                KASIM = CASE WHEN @month = 11 THEN @debit - @remaining ELSE 0 END,
                ARALIK = CASE WHEN @month = 12 THEN @debit - @remaining ELSE 0 END
                WHERE RECID = @recid;
            END
            
            FETCH NEXT FROM MY_CURSOR INTO @sign, @debit, @credit, @date, @lineexp, @accountcode, @invoiceno, @month, @accountname, @trcode;
        END;
        CLOSE MY_CURSOR;
        DEALLOCATE MY_CURSOR;
        ";
    } else {
        // --- ALACAK YAŞLANDIRMA (320) ---
        $logic = "
        -- Tahsilatlar Temp Tablo
        CREATE TABLE #tempdebits (
            [DEBIT] [float] NULL,
            [ACCOUNTCODE] [varchar](101) NULL,
            [REMAINING] [float] NULL
        );
        INSERT INTO #tempdebits
        SELECT SUM(DEBIT) as DEBIT, ACCOUNTCODE, SUM(DEBIT) as REMAINING
        FROM {$emfline}
        WHERE CANCELLED<>1 AND [SIGN]=0 AND ACCOUNTCODE LIKE '32%'
        GROUP BY ACCOUNTCODE;

        DECLARE @sign smallint, @debit float, @credit float, @date datetime;
        DECLARE @lineexp varchar(251), @accountcode varchar(101), @invoiceno varchar(17);
        DECLARE @month smallint, @trcode smallint, @recid int, @accountname varchar(200);
        
        SET @recid = 0;

        DECLARE MY_CURSOR CURSOR LOCAL STATIC READ_ONLY FORWARD_ONLY FOR 
        SELECT el.[SIGN], el.DEBIT, el.CREDIT, el.DATE_, el.LINEEXP, el.ACCOUNTCODE, el.INVOICENO, el.MONTH_, ca.DEFINITION_, el.TRCODE
        FROM {$emfline} el
        LEFT JOIN {$emuhacc} ca ON ca.LOGICALREF = el.ACCOUNTREF
        WHERE el.CANCELLED<>1 
        AND el.ACCOUNTCODE LIKE '32%'
        ORDER BY el.DATE_ ASC;

        OPEN MY_CURSOR;
        FETCH NEXT FROM MY_CURSOR INTO @sign, @debit, @credit, @date, @lineexp, @accountcode, @invoiceno, @month, @accountname, @trcode;

        WHILE @@FETCH_STATUS = 0
        BEGIN 
            SET @recid = @recid + 1;
            DECLARE @remaining FLOAT = 0;
            DECLARE @splitted smallint = 0;
            
            SELECT @remaining = ISNULL(REMAINING,0) FROM #tempdebits WHERE ACCOUNTCODE = @accountcode;
            
            IF @remaining = 0 OR @remaining IS NULL
            BEGIN
                INSERT INTO #tempresults VALUES(@recid, @sign, @debit, @credit, @date, @lineexp, @accountcode, @invoiceno, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, @accountname);
            END
            ELSE
            BEGIN
                IF NOT (@remaining - @credit < 0) AND @sign = 1
                BEGIN
                    UPDATE #tempdebits SET REMAINING = REMAINING - @credit WHERE ACCOUNTCODE = @accountcode;
                    INSERT INTO #tempresults VALUES(@recid, @sign, @debit, @credit, @date, @lineexp, @accountcode, @invoiceno, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, @accountname);
                END
                
                IF (@remaining - @credit < 0) AND @sign = 1 AND @remaining <> 0
                BEGIN
                    UPDATE #tempdebits SET REMAINING = 0 WHERE ACCOUNTCODE = @accountcode;
                    SET @splitted = 1;
                    INSERT INTO #tempresults VALUES(@recid, @sign, @debit, @remaining, @date, @lineexp, @accountcode, @invoiceno, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, @accountname);
                    SET @recid = @recid + 1;
                    INSERT INTO #tempresults VALUES(@recid, @sign, @debit, @credit - @remaining, @date, 'PARÇALANDI : ' + ISNULL(@lineexp,''), @accountcode, @invoiceno, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, @accountname);
                END
                
                IF @remaining = 0 AND @sign <> 0
                BEGIN
                    INSERT INTO #tempresults VALUES(@recid, @sign, @debit, @credit, @date, @lineexp, @accountcode, @invoiceno, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, @accountname);
                END
                
                IF @sign = 0
                BEGIN
                    INSERT INTO #tempresults VALUES(@recid, @sign, @debit, @credit, @date, @lineexp, @accountcode, @invoiceno, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, @accountname);
                END
            END
            
            IF ((@remaining - @credit < 0 AND @remaining <> 0) OR @remaining = 0) AND @sign = 1
            BEGIN
                UPDATE #tempresults
                SET 
                ACILIS = CASE WHEN @trcode = 1 THEN @credit - @remaining ELSE 0 END,
                OCAK = CASE WHEN @month = 1 AND @trcode <> 1 THEN @credit - @remaining ELSE 0 END,
                SUBAT = CASE WHEN @month = 2 THEN @credit - @remaining ELSE 0 END,
                MART = CASE WHEN @month = 3 THEN @credit - @remaining ELSE 0 END,
                NISAN = CASE WHEN @month = 4 THEN @credit - @remaining ELSE 0 END,
                MAYIS = CASE WHEN @month = 5 THEN @credit - @remaining ELSE 0 END,
                HAZIRAN = CASE WHEN @month = 6 THEN @credit - @remaining ELSE 0 END,
                TEMMUZ = CASE WHEN @month = 7 THEN @credit - @remaining ELSE 0 END,
                AGUSTOS = CASE WHEN @month = 8 THEN @credit - @remaining ELSE 0 END,
                EYLUL = CASE WHEN @month = 9 THEN @credit - @remaining ELSE 0 END,
                EKIM = CASE WHEN @month = 10 THEN @credit - @remaining ELSE 0 END,
                KASIM = CASE WHEN @month = 11 THEN @credit - @remaining ELSE 0 END,
                ARALIK = CASE WHEN @month = 12 THEN @credit - @remaining ELSE 0 END
                WHERE RECID = @recid;
            END
            
            FETCH NEXT FROM MY_CURSOR INTO @sign, @debit, @credit, @date, @lineexp, @accountcode, @invoiceno, @month, @accountname, @trcode;
        END;
        CLOSE MY_CURSOR;
        DEALLOCATE MY_CURSOR;
        ";
    }

    $summaryQuery = "
    SELECT 
        ACCOUNTCODE,
        ACCOUNTNAME,
        SUM(DEBIT) AS BORC,
        SUM(CREDIT) AS ALACAK,
        SUM(DEBIT) - SUM(CREDIT) AS BAKIYE,
        SUM(ACILIS) as Acilis,
        SUM(OCAK) as Ocak,
        SUM(SUBAT) as Subat,
        SUM(MART) as Mart,
        SUM(NISAN) as Nisan,
        SUM(MAYIS) as Mayis,
        SUM(HAZIRAN) as Haziran,
        SUM(TEMMUZ) as Temmuz,
        SUM(AGUSTOS) as Agustos,
        SUM(EYLUL) as Eylul,
        SUM(EKIM) as Ekim,
        SUM(KASIM) as Kasim,
        SUM(ARALIK) as Aralik,
        ((SUM(" . ($type === 'debit' ? "DEBIT" : "CREDIT") . ") - SUM(" . ($type === 'debit' ? "CREDIT" : "DEBIT") . ")) - SUM(ACILIS) - SUM(OCAK) - SUM(SUBAT) - SUM(MART) - SUM(NISAN) - SUM(MAYIS) - SUM(HAZIRAN) - SUM(TEMMUZ) - SUM(AGUSTOS) - SUM(EYLUL) - SUM(EKIM) - SUM(KASIM) - SUM(ARALIK)) AS SAGLAMA
    FROM #tempresults
    GROUP BY ACCOUNTCODE, ACCOUNTNAME
    
    -- Exclude balanced accounts if needed? User screenshot shows some small balances.
    -- ORDER BY ACCOUNTCODE
    ";

    return $commonSetup . $logic . $summaryQuery;
}
?>
