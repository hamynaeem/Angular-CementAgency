import { Component, OnInit, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import {
  GetDateJSON,
  JSON2Date,
  formatNumber,
} from '../../../factories/utilities';
import { CachedDataService } from '../../../services/cacheddata.service';
import { HttpBase } from '../../../services/httpbase.service';
import { PrintDataService } from '../../../services/print.data.services';
import { MyToastService } from '../../../services/toaster.server';

@Component({
  selector: 'app-sale-summary',
  templateUrl: './sale-summary.component.html',
  styleUrls: ['./sale-summary.component.scss'],
})
export class SalesummaryComponent implements OnInit {
  @ViewChild('RptTable') RptTable: any;

  public Filter = {
    FromDate: GetDateJSON(),
    ToDate: GetDateJSON(),
    ProductID: '',
  };
  setting = {
    Checkbox: false,
    Columns: [
      {
        label: 'Product Name',
        fldName: 'ProductName',
      },
      {
        label: 'Price',
        fldName: 'SPrice',
        sum: true,
      },
      {
        label: 'Qty',
        fldName: 'Qty',
        sum: true,
      },
      {
        label: 'Amount',
        fldName: 'Amount',
        sum: true,
        valueFormatter: (d: any) => {
          return formatNumber(d['Amount']);
        },
      },
    ],
    Actions: [],
    Data: [],
  };
  sting = this.setting;
  public data: object[] = [];
  public Items: any;
  constructor(
    private http: HttpBase,
    private cachedData: CachedDataService,
    private ps: PrintDataService,
    private router: Router
  ) {}

  ngOnInit() {
    this.Items = this.cachedData.Products$;
    this.FilterData();
  }
  PrintReport() {
    this.ps.PrintData.HTMLData = document.getElementById('print-section');
    this.ps.PrintData.Title = 'Sale Summary Report';
    this.ps.PrintData.SubTitle =
      'From :' +
      JSON2Date(this.Filter.FromDate) +
      ' To: ' +
      JSON2Date(this.Filter.ToDate);

    this.router.navigateByUrl('/print/print-html');
  }
  FilterData() {
    // tslint:disable-next-line:quotemark
    let filter =
      "Date between '" +
      JSON2Date(this.Filter.FromDate) +
      "' and '" +
      JSON2Date(this.Filter.ToDate) +
      "'";

    if (this.Filter.ProductID && this.Filter.ProductID != '') {
      filter += ' And ProductID=' + this.Filter.ProductID;

      this.http
        .getData(
          'qrysalereport?flds=InvoiceID,ProductName,SPrice,' +
            'Qty as Qty, Amount as Amount&filter=' +
            filter
        )
        .then((r: any) => {
          this.sting = JSON.parse(JSON.stringify(this.setting));
          this.sting.Columns.unshift({
            label: 'Bill No',
            fldName: 'InvoiceID',
          });
          this.data = r;
        });
    } else {
      this.http
        .getData(
          'qrysalereport?flds=ProductName,SPrice,Qty as Qty, ' +
            'Amount as Amount&filter=' +
            filter
        )
        .then((r: any) => {
          this.sting = JSON.parse(JSON.stringify(this.setting));
          this.data = r;
        });
    }
  }
}
