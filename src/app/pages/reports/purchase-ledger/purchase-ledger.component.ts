import { Component, OnInit, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import { GetDateJSON, JSON2Date } from '../../../factories/utilities';
import { CachedDataService } from '../../../services/cacheddata.service';
import { HttpBase } from '../../../services/httpbase.service';
import { PrintDataService } from '../../../services/print.data.services';

@Component({
  selector: 'app-purchase-ledger',
  templateUrl: './purchase-ledger.component.html',
  styleUrls: ['./purchase-ledger.component.scss'],
})
export class PurchaseLedgerComponent implements OnInit {
  @ViewChild('RptTable') RptTable;

  public Filter = {
    FromDate: GetDateJSON(),
    ToDate: GetDateJSON(),
    ItemID: '',

    CustomerID: '',
  };
  setting = {
    Checkbox: false,
    Columns: [
      {
        label: 'Date',
        fldName: 'Date',
      },
      {
        label: 'Bill No',
        fldName: 'BookingID',
      },
      {
        label: 'Product Name',
        fldName: 'ProductName',
      },

      {
        label: 'Qty (Tons)',
        fldName: 'Qty',
        sum: true,
      },
      {
        label: 'Price',
        fldName: 'PPrice',
      },
      {
        label: 'Amount',
        fldName: 'Amount',
        sum: true,
      },
    ],
    Actions: [],
    Data: [],
  };

  nWhat = '1';
  Items: any = [{ ItemID: '1', ItemName: 'Test Item' }];

  public data: object[];
  public Accounts: any;
  public selectedCustomer: any = {};
  constructor(
    private http: HttpBase,
    private ps: PrintDataService,
    private cachedData: CachedDataService,
    private router: Router
  ) {
    this.Accounts = this.cachedData.Accounts$;
  }

  ngOnInit() {
    this.LoadItems();
    this.FilterData();
  }
  PrintReport() {
    this.ps.PrintData.HTMLData = document.getElementById('print-section');
    this.ps.PrintData.Title =
      'Purchase Ledger ' +
      (this.Filter.CustomerID
        ? ' Customer: ' + this.selectedCustomer.CustomerName
        : '');
    this.ps.PrintData.SubTitle =
      'From :' +
      JSON2Date(this.Filter.FromDate) +
      ' To: ' +
      JSON2Date(this.Filter.ToDate);

    this.router.navigateByUrl('/print/print-html');
  }
  CustomerSelected(e) {
    console.log(e);
    this.selectedCustomer = e;
  }
  FilterData() {
    // tslint:disable-next-line:quotemark
    let filter =
      "Date between '" +
      JSON2Date(this.Filter.FromDate) +
      "' and '" +
      JSON2Date(this.Filter.ToDate) +
      "'";

    if (this.Filter.CustomerID)
      filter += ' and CustomerID=' + this.Filter.CustomerID;

    if (this.Filter.ItemID)
      if (this.nWhat == '1') filter += ' and ProductID=' + this.Filter.ItemID;
      else filter += ' and UnitID=' + this.Filter.ItemID;

    let flds =
      'Date,BookingID, ProductName, Qty, PPrice, Amount';

    this.http
      .getData(
        `qrypurchasereport?orderby=Date,BookingID&flds=${flds}&filter=${filter}`
      )
      .then((r: any) => {
        this.data = r;
      });
  }
  Clicked(e) {}

  ItemSelected(e) {}
  ItemChange(e) {
    this.LoadItems();
  }
  async LoadItems() {
    this.Items = [];
    if (this.nWhat == '1') {
      this.cachedData.Products$.subscribe((r: any) => {
        r.forEach((m) => {
          this.Items.push({
            ItemID: m.ProductID,
            ItemName: m.ProductName,
          });
        });
        this.Items = [...this.Items];
        console.log(this.Items);
      });
    } else if (this.nWhat == '2') {
      this.http.getData('units').then((r: any) => {
        r.forEach((m) => {
          this.Items.push({
            ItemID: m.ID,
            ItemName: m.UnitName,
          });
          this.Items = [...this.Items];
        });
      });
    }
  }
}
