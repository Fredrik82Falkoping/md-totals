public class Article
    {
        //[Key, DatabaseGenerated(DatabaseGeneratedOption.None)]

        public long ulKey { get; set; }
        [MaxLength(32)]
        public string strProductKey { get; set; } = string.Empty;
        public ulong ulPlu { get; set; }
        public long ulParentArticleKey { get; set; }
        public ulong ulDeptKey { get; set; }
        public ulong ulGroupKey { get; set; }
        public E_CODE_TYPE enCodeType { get; set; }
        public bool bVariablePrice { get; set; }

        //pricing
        public E_UOM enUOM { get; set; }
        public E_UOM enPricePerUom { get; set; }
        public ulong ulQty { get; set; }
        public ulong ulPriceOrUnitPrice { get; set; }
        public ulong ulUnitPrice { get; set; }           //unused, because we are using 'ulPriceOrUnitPrice'
        public ulong ulDeposit { get; set; }             //
        public double dCostPrice { get; set; }           //cost price can be some unnatural behaviour e.g. higher resolution than cent
                                                         //that's why we make an exception here by using a double instead of an int
        public E_MKDOWN_TYPE enMkDownType { get; set; }  //refer to E_MkDownType
        public ulong ulMkDownValue { get; set; }          //depending on byMkDownType used to build the new price
        public bool bInBargain { get; set; }
        public ulong ulBargainPriceOrUnitPrice { get; set; }     //
        public ushort usLeadTime { get; set; }

        public LABELINGCFG lbcfgNormal { get; set; } = new() { bCombiLabelFg = true, byNoOfCombiLabels = 1, byNoOfPriceLabels = 1, byNoOfBarcodeLabels = 1 };
        public LABELINGCFG lbcfgMd { get; set; } = new() { bCombiLabelFg = false, byNoOfPriceLabels = 1, byNoOfBarcodeLabels = 1 };

        public ushort usLibtxtNormalKey { get; set; }
        public ushort usLibtxtMarkDownKey { get; set; }
        public ushort usLibtxtBargainKey { get; set; }

        //LMKV/LMIV
        public E_EXPIRATION_DATE_TYPE enByExpirationDateType { get; set; }  //0= best before type, 1= eat bytype
        public DateTime dtProduced { get; set; }
        public DateTime dtSellBy { get; set; }
        public DateTime dtUseByBy { get; set; }

        public ulong ulCountryOfOriginKey { get; set; }
        public ushort usExpirationDaysDays { get; set; }     //default
        public ushort usExpirationDaysDays2 { get; set; }    //if someone needs best before and use bay

        public short iStorageTemperature { get; set; }   //can be negative, e.g. -18 °C -> signed int
        public long iIngredientsNo { get; set; }        //we may indicate this number as default value -> default value might be indicated as negative value
        public long iNutrifactsNo { get; set; }         //we may indicate this number as default value -> default value might be indicated as negative value

        //ordering information
        public ulong ulSupplierID { get; set; }

        //commodity name
        [MaxLength(64)]
        public string strCommodityName { get; set; } = string.Empty;

        [MaxLength(64)]
        public string strCommodityName2 { get; set; } = string.Empty;

#if false
        public bool HasInvalidKey() { return ulKey < 1 || ulKey > 999999999999; }
        public bool HasParent() { return ulParentArticleKey != 0 && ulParentArticleKey != ulKey; }
        public bool IsInPluRange()