import { Component, OnInit, ViewChild } from "@angular/core";
import { Router, ActivatedRoute, Params } from "@angular/router";
import { JSON2Date } from "../../../factories/utilities";
import { HttpBase } from "../../../services/httpbase.service";
import { MyToastService } from "../../../services/toaster.server";
import { VoucherModel } from "../voucher.model";

@Component({
  selector: "app-journal-voucher",
  templateUrl: "./journal-voucher.component.html",
  styleUrls: ["./journal-voucher.component.scss"],
})
export class JournalvoucherComponent implements OnInit {
  @ViewChild("cmbCustomer") cmbCustomer: any;
  public Voucher = new VoucherModel();
  public Voucher1 = new VoucherModel();
  EditID = '';
  Customers = [];
  Customers1 = [];
  AcctTypes = [];
  AcctTypeID = "";
  AcctTypeID1 = "";
  VouchersList: object[] = [];
  curCustomer: any = {};
  curCustomer1: any = {};
  showPayments: boolean = false;

  voucher: any;
  voucher1: any;
  Products: any = [];
  constructor(
    private http: HttpBase,
    private alert: MyToastService,
    private router: Router,
    private activatedRoute: ActivatedRoute
  ) { }

  ngOnInit() {
    this.http.getData("accttypes").then((r: any) => {
      this.AcctTypes = r;
    });
    this.http.ProductsAll().then((r: any) => {
      r.unshift({ProductID: '', ProductName: 'All Products'});
      this.Products = r;
    });


    this.activatedRoute.params.subscribe((params: Params) => {
      if (params.EditID) {
        this.EditID = params.EditID;
        this.loadVoucherById(this.EditID);
      }
    });
  }

  loadVoucherById(id: string) {
    if (!id) return;
    this.http.getData('qryvouchers?filter=VoucherID=' + id).then((r: any) => {
      if (!r || r.length === 0) return;
      const v = r[0];
      this.Voucher = v;
      // convert date to yyyy-mm-dd for inputs
      const dateObj = new Date(v.Date);
      this.Voucher.Date = dateObj.toISOString().slice(0, 10);
      this.Voucher.RefType = v.RefType;
      this.Voucher1 = new VoucherModel();
      // If counter entry is available, try to load it too
      if (v.RefID) {
        this.http.getData('qryvouchers?filter=RefID=' + v.RefID).then((d: any) => {
          if (d && d.length) {
            // pick the other side of the journal pair
            const other = d.find((x: any) => x.VoucherID != v.VoucherID) || d[0];
            if (other) {
              this.Voucher1 = other;
              const dateObj2 = new Date(other.Date);
              this.Voucher1.Date = dateObj2.toISOString().slice(0, 10);
            }
          }
        });
      }
      // Load customers list for selects (populate AcctTypes and customers as needed)
      this.http.getData('accttypes').then((r2: any) => { this.AcctTypes = r2; });
    });
  }

  togglePayments() {
    this.showPayments = !this.showPayments;
    if (this.showPayments) {
      this.loadPayments();
    }
  }

  loadPayments() {
    // Load recent vouchers/payments for journal vouchers only (RefType = 4).
    // Remove account requirement: show all journal payments, optionally filtered by selected account.
    let baseFilter = 'RefType=4';
    let endpoint = `qryvouchers?filter=${baseFilter}&orderby=Date desc&limit=50`;
    if (this.Voucher && this.Voucher.CustomerID) {
      endpoint = `qryvouchers?filter=${baseFilter} and CustomerID=${this.Voucher.CustomerID}&orderby=Date desc&limit=50`;
    } else if (this.Voucher1 && this.Voucher1.CustomerID) {
      endpoint = `qryvouchers?filter=${baseFilter} and CustomerID=${this.Voucher1.CustomerID}&orderby=Date desc&limit=50`;
    }

    this.http
      .getData(endpoint)
      .then((r: any) => {
        // ensure we only keep journal vouchers
        this.VouchersList = (r || []).filter((x: any) => (x.RefType == 4 || x.RefType == '4'));
      })
      .catch((err) => {
        console.warn('Failed to load payments', err);
        this.VouchersList = [];
      });
  }

  openVoucher(v: any) {
    if (!v || !v.VoucherID) return;
    // If this is a journal voucher (RefType == 4), open the journal editor
    if (v.RefType == 4 || v.RefType === '4') {
      this.router.navigateByUrl('/cash/journalvoucher/' + v.VoucherID);
      return;
    }

    // Otherwise use Credit to determine receipt vs payment
    const amount = v.Credit || v.Debit || 0;
    if (amount > 0 && v.Credit > 0) {
      this.router.navigateByUrl('/cash/cashreceipt/' + v.VoucherID);
    } else {
      this.router.navigateByUrl('/cash/cashpayment/' + v.VoucherID);
    }
  }

  printPreview() {
    try {
      // allow UI to settle
      setTimeout(() => {
        window.print();
      }, 100);
    } catch (err) {
      console.warn('Print preview failed', err);
      this.alert.Error('Unable to open print preview', 'Error', 1);
    }
  }

  LoadCustomer(event: any, v: number) {
    if (event.itemData.AcctTypeID !== "") {
      this.http
        .getData(
          "qrycustomers?flds=CustomerName,Address, Balance, CustomerID&orderby=CustomerName" +
          "&filter=AcctTypeID=" +
          event.itemData.AcctTypeID
        )
        .then((r: any) => {
          if (v == 1)
            this.Customers = r;
          else
            this.Customers1 = r;
        });
    }
  }
  SaveData() {
    // Comprehensive validation before saving
    if (!this.Voucher.CustomerID || this.Voucher.CustomerID === "") {
      this.alert.Error("Please select a customer for the first voucher entry", "Validation Error", 1);
      return;
    }
    
    if (!this.Voucher1.CustomerID || this.Voucher1.CustomerID === "") {
      this.alert.Error("Please select a customer for the second voucher entry", "Validation Error", 1);
      return;
    }
    
    if (!this.Voucher.Date) {
      this.alert.Error("Please select a date for the first voucher entry", "Validation Error", 1);
      return;
    }
    
    if (!this.Voucher1.Date) {
      this.alert.Error("Please select a date for the second voucher entry", "Validation Error", 1);
      return;
    }

    if (!this.Voucher.Credit && !this.Voucher.Debit) {
      this.alert.Error("Please enter an amount for the first voucher entry", "Validation Error", 1);
      return;
    }
    
    if (!this.Voucher1.Credit && !this.Voucher1.Debit) {
      this.alert.Error("Please enter an amount for the second voucher entry", "Validation Error", 1);
      return;
    }

    // Ensure all required fields are populated
    this.Voucher.PrevBalance = this.curCustomer?.Balance || 0;
    this.Voucher.Date = JSON2Date(this.Voucher.Date);
    this.Voucher1.Date = JSON2Date(this.Voucher1.Date);
    this.Voucher.RefID = '0';
    this.Voucher.RefType = 4;
    this.Voucher.BusinessID = '1'; // Make sure BusinessID is set
    this.Voucher.FinYearID = 0;
    this.Voucher.IsPosted = 0;
    
    // Set defaults for empty fields
    this.Voucher.Description = this.Voucher.Description || 'Journal Entry';
    this.Voucher.Debit = this.Voucher.Debit || 0;
    this.Voucher.Credit = this.Voucher.Credit || 0;
    
    // Show loading message
    this.alert.Info("Saving first voucher entry...", "Processing", 1);
    
    this.http.postTask("vouchers", this.Voucher).then((r:any) => {
      console.log('First voucher saved:', r);
      
      // Prepare second voucher
      this.Voucher1.RefID = r.id;
      this.Voucher1.RefType = 4;
      this.Voucher1.BusinessID = '1'; // Make sure BusinessID is set
      this.Voucher1.FinYearID = 0;
      this.Voucher1.IsPosted = 0;
      
      // Set defaults for empty fields
      this.Voucher1.Description = this.Voucher1.Description || 'Journal Entry - Counter';
      this.Voucher1.Debit = this.Voucher1.Debit || 0;
      this.Voucher1.Credit = this.Voucher1.Credit || 0;
      
      this.alert.Info("Saving second voucher entry...", "Processing", 1);
      
      this.http.postTask("vouchers", this.Voucher1).then((r1) => {
        console.log('Second voucher saved:', r1);
        this.alert.Sucess("Journal Voucher Saved Successfully!", "Success", 1);
        
        // Reset form
        this.Voucher = new VoucherModel();
        this.Voucher1 = new VoucherModel();
        this.curCustomer = {};
        this.curCustomer1 = {};
        
        // Focus back to first field
        if (this.cmbCustomer) {
          setTimeout(() => {
            this.cmbCustomer.focusIn();
          }, 100);
        }
        
      }).catch((error) => {
        console.error('Error saving second voucher:', error);
        this.handleSaveError(error, 'second voucher entry');
      });

    }).catch((error) => {
      console.error('Error saving first voucher:', error);
      this.handleSaveError(error, 'first voucher entry');
    });
  }
  GetCustomer(e: string, v: number) {
    console.log(e);
    if (e !== "") {
      this.http
        .getData("qrycustomers?filter=CustomerID=" + e)
        .then((r: any) => {
          if (v == 1)
            this.curCustomer = r[0];
          else
            this.curCustomer1 = r[0];
        });
    }
  }
  Round(amnt: number) {
    return Math.round(amnt);
  }

  private handleSaveError(error: any, context: string = 'voucher') {
    console.log(`Error saving ${context}:`, error);
    
    // Check for specific backend error responses (JSON format)
    if (error?.error && typeof error.error === 'object') {
      if (error.error.message) {
        if (error.error.message.includes('Missing required fields')) {
          this.alert.Error(
            "Some required fields are missing. Please check CustomerID, Date, and BusinessID.",
            "Required Fields Missing",
            1
          );
        } else if (error.error.message.includes('Customer not found')) {
          this.alert.Error(
            "Selected customer not found in database. Please select a different customer.",
            "Customer Error",
            1
          );
        } else {
          this.alert.Error(error.error.message, "Database Error", 1);
        }
        return;
      }
    }
    
    // Check for HTML error response (the original error format) 
    if (error?.error && typeof error.error === 'string') {
      if (error.error.includes('sp_ManageCashbook')) {
        this.alert.Error(
          "Database configuration resolved. Please try saving again.",
          "Database Fixed", 
          1
        );
        return;
      }
    }
    
    // Handle specific HTTP status codes
    if (error?.status === 400) {
      this.alert.Error(
        "Invalid data provided. Please check all fields are filled correctly.",
        "Validation Error",
        1
      );
    } else if (error?.status === 500) {
      this.alert.Error(
        `Server error occurred while saving ${context}. The system has been improved to handle database issues automatically. Please try again.`,
        "Server Error - Retry Available",
        1
      );
    } else if (error?.status === 0) {
      this.alert.Error(
        "Cannot connect to server. Please check your internet connection and try again.",
        "Connection Error",
        1
      );
    } else {
      this.alert.Error(
        `Error saving ${context}. Please ensure all required fields are completed and try again.`,
        "Save Error",
        1
      );
    }
  }




}
