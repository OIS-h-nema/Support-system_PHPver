USE [SUPPORTDB]
GO

/****** Object:  Table [dbo].[SQL_顧客]    Script Date: 2025/12/04 16:07:00 ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE TABLE [dbo].[SQL_顧客](
	[SEQNO] [int] NOT NULL,
	[PARENT_SEQ] [int] NULL,
	[顧客コード] [int] NULL,
	[部門コード] [int] NULL,
	[顧客名] [nvarchar](50) NULL,
	[顧客名カナ] [nvarchar](50) NULL,
	[顧客略称] [nvarchar](50) NULL,
	[郵便番号] [nvarchar](50) NULL,
	[住所] [nvarchar](50) NULL,
	[電話番号] [nvarchar](50) NULL,
	[FAX] [nvarchar](50) NULL,
	[更新日] [datetime] NULL,
 CONSTRAINT [PK_SQL_顧客] PRIMARY KEY CLUSTERED 
(
	[SEQNO] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY]
GO

