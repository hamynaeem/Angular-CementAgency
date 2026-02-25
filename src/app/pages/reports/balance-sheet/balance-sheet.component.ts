import { Component, OnInit } from '@angular/core';
import { getDMYDate } from '../../../factories/utilities';
import { HttpBase } from '../../../services/httpbase.service';
import { Router } from '@angular/router';
import { PrintDataService } from '../../../services/print.data.services';
import { formatNumber } from '../../../factories/utilities';
@Component({
  selector: "app-balance-sheet",
  templateUrl: "./balance-sheet.component.html",
  styleUrls: ["./balance-sheet.component.scss"],
})
export class BalanceSheetComponent implements OnInit {
  public data: BalanceRow[] = [];
  public Salesman: object[] = [];
  public Routes: object[] = [];

  curCustomer: any = {};
  VouchersList: object[] = [];

  dteDate = getDMYDate();
  setting = {
    Columns: [
      {
        label: "Type",
        fldName: "Type",
      },
      {
        label: "Customer Name",
        fldName: "CustomerName",
      },
      {
        label: "Credit",
        fldName: "Credit",
        sum: true,
        valueFormatter: (d: any) => {
          return formatNumber(d["Credit"]);
        },
      },
      {
        label: "Debit",
        fldName: "Debit",
        sum: true,
        valueFormatter: (d: any) => {
          return formatNumber(d["Debit"]);
        },
      },
    ],
    Actions: [],
    Data: [] as BalanceRow[],
  };
  isLoading = false;
  errorMsg = '';

  constructor(
    private http: HttpBase,
    private ps: PrintDataService,
    private router: Router
  ) {}

  ngOnInit() {
    this.isLoading = true;
    this.errorMsg = '';
    this.http
      .getData('balancesheet')
      .then((r: any) => {
        this.data = Array.isArray(r) ? r : [];
        this.setting.Data = this.data;
      })
      .catch((err: any) => {
        this.data = [];
        this.setting.Data = [];
        try {
          if (err && err.message) this.errorMsg = err.message;
          else if (err && err.error) this.errorMsg = typeof err.error === 'string' ? err.error : JSON.stringify(err.error);
          else this.errorMsg = 'Failed to load balance sheet data.';
        } catch (e) {
          this.errorMsg = 'Failed to load balance sheet data.';
        }
      })
      .finally(() => {
        this.isLoading = false;
      });
  }
  PrintReport() {
    this.ps.PrintData.HTMLData = document.getElementById("print-section");
    this.ps.PrintData.Title = "Balance Sheet";
    this.ps.PrintData.SubTitle = "As On  :" + this.dteDate;

    this.router.navigateByUrl("/print/print-html");
  }

}

interface BalanceRow {
  Type?: string;
  CustomerName?: string;
  Credit?: number;
  Debit?: number;
  [key: string]: any;
}
