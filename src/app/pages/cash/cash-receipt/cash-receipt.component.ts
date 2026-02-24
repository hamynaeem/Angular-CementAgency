import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { ActivatedRoute, Params, Router } from '@angular/router';
import { Buttons } from '../../../../../../../libs/future-tech-lib/src/lib/components/navigator/navigator.component';
import {
  GetDateJSON,
  JSON2Date
} from '../../../factories/utilities';
import { HttpBase } from '../../../services/httpbase.service';
import { MyToastService } from '../../../services/toaster.server';
import { VoucherModel } from '../voucher.model';

@Component({
  selector: 'app-cash-receipt',
  templateUrl: './cash-receipt.component.html',
  styleUrls: ['./cash-receipt.component.scss'],
})
export class CashReceiptComponent implements OnInit {
  @ViewChild('cmbCustomer') cmbCustomer!: ElementRef;
  public Voucher = new VoucherModel();
  Customers = [];
  AcctTypes = [];
  EditID = '';
  public Ino = '';
  private AcctTypeID = '';
  curCustomer: any = {};
  Products: any = [];
  constructor(
    private http: HttpBase,
    private alert: MyToastService,
    private router: Router,

    private activatedRoute: ActivatedRoute
  ) {}

  ngOnInit() {
    // this.http.getData('accttypes').then((r: any) => {
    //   this.AcctTypes = r;
    // });
    this.LoadCustomer('');

    this.activatedRoute.params.subscribe((params: Params) => {
      if (params.EditID) {
        this.EditID = params.EditID;
        this.Ino = this.EditID;
        this.http
          .getData('qryvouchers?filter=VoucherID=' + this.EditID)
          .then((r: any) => {
            this.Voucher = r[0];
            this.Voucher.Date = GetDateJSON(new Date(r[0].Date));
            this.LoadCustomer({ AcctTypeID: r[0].AcctTypeID });
            this.GetCustomer(this.Voucher.CustomerID);
          });
      } else {
        this.EditID = '';
      }
      console.log(this.EditID);
    });
  }
  async FindINo() {
    let voucher:any = await this.http.getData( 'vouchers/' + this.Ino)
    if (voucher.Credit > 0 )
      this.router.navigate(['/cash/cashreceipt/', this.Ino]);
    else
      this.router.navigate(['/cash/cashpayment/', this.Ino]);
  }
  LoadCustomer(event?: any) {
      this.http
        .getData(
          'qrycustomers?flds=CustomerName,Address, Balance, CustomerID&orderby=CustomerName'
        )
        .then((r: any) => {
          this.Customers = r;
        });
  }
  SaveData() {
    let voucherid = '';
    this.Voucher.PrevBalance = this.curCustomer.Balance;
    
    // Set default values for required fields if missing
    if (!this.Voucher.RefID) this.Voucher.RefID = "0";
    if (!this.Voucher.IsPosted) this.Voucher.IsPosted = 0;
    if (!this.Voucher.FinYearID) this.Voucher.FinYearID = 1;
    if (!this.Voucher.RefType) this.Voucher.RefType = 0;
    
    // Add BusinessID to the voucher object (required by backend)
    (this.Voucher as any).BusinessID = 1;
    
    // Format date for PHP backend (Y-m-d format)
    if (this.Voucher.Date) {
      let dateObj: Date;
      if (typeof this.Voucher.Date === 'object') {
        const d: any = this.Voucher.Date;
        dateObj = new Date(d.year, d.month - 1, d.day);
      } else {
        dateObj = new Date(this.Voucher.Date);
      }
      if (!dateObj || isNaN(dateObj.getTime())) {
        this.alert.Error('Invalid date format', 'Error', 1);
        return;
      }
      this.Voucher.Date = dateObj.toISOString().slice(0, 10);
    } else {
      this.alert.Error('Date is required', 'Error', 1);
      return;
    }
    
    // Check for other required fields
    if (!this.Voucher.CustomerID) {
      this.alert.Error('Please select a customer', 'Error', 1);
      return;
    }
    if (!this.Voucher.Description) {
      this.alert.Error('Description is required', 'Error', 1);
      return;
    }
    if (!this.Voucher.Debit && !this.Voucher.Credit) {
      this.alert.Error('Either Debit or Credit amount is required', 'Error', 1);
      return;
    }
    
    if (this.EditID != '') {
      voucherid = '/' + this.EditID;
    }
    this.AcctTypeID = this.Voucher.AcctTypeID;
    console.log('Sending voucher data:', this.Voucher);
    const payload: any = {
      Date: this.Voucher.Date,
      CustomerID: this.Voucher.CustomerID,
      Description: this.Voucher.Description,
      Debit: this.Voucher.Debit || 0,
      Credit: this.Voucher.Credit || 0,
      RefID: this.Voucher.RefID || '0',
      IsPosted: this.Voucher.IsPosted || 0,
      FinYearID: this.Voucher.FinYearID || 1,
      RefType: this.Voucher.RefType || 0,
      BusinessID: (this.Voucher as any).BusinessID || this.http.getBusinessID(),
      RouteID: this.Voucher.RouteID || null,
      SalesmanID: this.Voucher.SalesmanID || null,
      ClosingID: this.Voucher.ClosingID || null,
      PrevBalance: this.Voucher.PrevBalance || 0
    };

    this.http
      .postTask('vouchers' + voucherid, payload)
      .then((r) => {
        this.alert.Sucess('Receipt Saved', 'Save', 1);
        if (this.EditID != '') {
          this.router.navigateByUrl('/cash/cashreceipt/');
        } else {
          this.Voucher = new VoucherModel();
          this.cmbCustomer.nativeElement.focus();
        }
      })
      .catch((err) => {
        this.Voucher.Date = GetDateJSON();
        console.error('Voucher save error:', err);
        // Show server error message when available, otherwise show status text
        const serverMsg = err && err.error && (err.error.message || err.error) ? (err.error.message || err.error) : (err.statusText || 'Server error');
        this.alert.Error(serverMsg, 'Error', 1);
      });
  }
  GetCustomer(CustomerID: string) {
    console.log(CustomerID);

    if (CustomerID && CustomerID !== '') {
      this.http
        .getData('qrycustomers?filter=CustomerID=' + CustomerID)
        .then((r: any) => {
          this.curCustomer = r[0];
          this.Voucher.AcctTypeID = this.curCustomer.AcctTypeID;
        });
    }
  }
  Round(amnt: number) {
    return Math.round(amnt);
  }
  NavigatorClicked(e: any) {
    let billNo = 240000001;
    switch (Number(e.Button)) {
      case Buttons.First:
        this.http.getData('getvouchno/R/0/F').then((r:any)=>{

          this.router.navigateByUrl('/cash/cashreceipt/' + r.Vno);
        })
        break;
      case Buttons.Previous:
        this.http.getData('getvouchno/R/' + this.EditID + "/B").then((r:any)=>{

          this.router.navigateByUrl('/cash/cashreceipt/' + r.Vno);
        })
        break;
      case Buttons.Next:
        this.http.getData('getvouchno/R/' + this.EditID + "/N").then((r:any)=>{

          this.router.navigateByUrl('/cash/cashreceipt/' + r.Vno);
        })
        break;
      case Buttons.Last:
        this.http.getData('getvouchno/R/0/L').then((r:any)=>{

          this.router.navigateByUrl('/cash/cashreceipt/' + r.Vno);
        })
        break;
      default:
        break;
    }
    //this.router.navigateByUrl('/sale/wholesale/' + billNo);
  }
  Add() {
    this.router.navigateByUrl('/cash/cashreceipt');
  }
  Cancel(){
    this.Voucher = new VoucherModel();
    this.router.navigateByUrl('/cash/cashreceipt');
  }
}
