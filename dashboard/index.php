<?php
include '../function/config.php';

$monthly_users_enabled = array_fill(1, 12, 0);
$monthly_users_disabled = array_fill(1, 12, 0);
$year = date('Y');
$ou_list = [
  "Dashboard" => null,
  "GSU" => "OU=GSU,OU=Cepu Group,DC=kangcepu,DC=com",
    "PSU" => "OU=PSU,OU=Cepu Group,DC=kangcepu,DC=com",
    "RU" => "OU=RU,OU=Cepu Group,DC=kangcepu,DC=com",
    "UCF" => "OU=UCF,OU=Cepu Group,DC=kangcepu,DC=com",
    "UCO" => "OU=UCO,OU=Cepu Group,DC=kangcepu,DC=com",
    "Users" => "CN=Users,DC=kangcepu,DC=com",
    "Computer" => "CN=Computers,DC=kangcepu,DC=com",
    "USB Allowed" => "OU=USB Allowed,OU=Cepu Computers,DC=kangcepu,DC=com",
    "USB Restricted" => "OU=USB Restricted,OU=Cepu Computers,DC=kangcepu,DC=com"
];
$ou_user_counts = array_fill(0, count(array_filter($ou_list)), 0);
if ($ldap_conn) {
  $base_dn = "DC=kangcepu,DC=com";
  $filter = "(&(objectClass=user)(whenCreated>={$year}0101000000.0Z))";
  $search = ldap_search($ldap_conn, $base_dn, $filter, ['whenCreated', 'userAccountControl']);
  if ($search) {
    $entries = ldap_get_entries($ldap_conn, $search);
    for ($i = 0; $i < $entries['count']; $i++) {
      if (!empty($entries[$i]['whencreated'][0])) {
        $date = DateTime::createFromFormat('YmdHis\.0\Z', $entries[$i]['whencreated'][0]);
        if ($date && $date->format('Y') == $year) {
          $month = (int)$date->format('n');
          $uac = $entries[$i]['useraccountcontrol'][0] ?? 512;
          $is_disabled = ($uac & 2) === 2;
          if ($is_disabled) {
            $monthly_users_disabled[$month]++;
          } else {
            $monthly_users_enabled[$month]++;
          }
        }
      }
    }
  }
  $index = 0;
  foreach ($ou_list as $label => $dn) {
    if ($dn) {
      $search = @ldap_search($ldap_conn, $dn, '(objectClass=user)', ['cn']);
      $ou_user_counts[$index++] = $search ? ldap_count_entries($ldap_conn, $search) : 0;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>IS Department Control System</title>
	<meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
	<link rel="icon" href="assets/img/icon.ico" type="image/x-icon"/>

	<!-- Fonts and icons -->
	<script src="../assets/js/plugin/webfont/webfont.min.js"></script>
	<script>
		WebFont.load({
			google: {"families":["Lato:300,400,700,900"]},
			custom: {"families":["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], urls: ['../assets/css/fonts.min.css']},
			active: function() {
				sessionStorage.fonts = true;
			}
		});
	</script>

	<!-- CSS Files -->
	<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="../assets/css/atlantis.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="../assets/css/demo.css">
</head>
<body>
	<div class="wrapper">
		<div class="main-header">
			<div class="logo-header" data-background-color="blue">
				
				<a href="index.php" class="logo">
					<img src="../assets/img/logo.svg" alt="navbar brand" class="navbar-brand">
				</a>
				<button class="navbar-toggler sidenav-toggler ml-auto" type="button" data-toggle="collapse" data-target="collapse" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon">
						<i class="icon-menu"></i>
					</span>
				</button>
				<button class="topbar-toggler more"><i class="icon-options-vertical"></i></button>
				<div class="nav-toggle">
					<button class="btn btn-toggle toggle-sidebar">
						<i class="icon-menu"></i>
					</button>
				</div>
			</div>

			<!-- Navbar Header -->
			<nav class="navbar navbar-header navbar-expand-lg" data-background-color="blue2">
				
				<div class="container-fluid">
					<div class="collapse" id="search-nav">
						<form class="navbar-left navbar-form nav-search mr-md-3">
							<div class="input-group">
								<div class="input-group-prepend">
									<button type="submit" class="btn btn-search pr-1">
										<i class="fa fa-search search-icon"></i>
									</button>
								</div>
							</div>
						</form>
					</div>
					<ul class="navbar-nav topbar-nav ml-md-auto align-items-center">
						<li class="nav-item dropdown hidden-caret">
							<a class="dropdown-toggle profile-pic" data-toggle="dropdown" href="#" aria-expanded="false">
								<div class="avatar-sm">
									<img src="../assets/img/profile.jpg" alt="..." class="avatar-img rounded-circle">
								</div>
							</a>
							<ul class="dropdown-menu dropdown-user animated fadeIn">
								<div class="dropdown-user-scroll scrollbar-outer">
									<li>
										<div class="user-box">
											<div class="avatar-lg"><img src="../assets/img/profile.jpg" alt="image profile" class="avatar-img rounded"></div>
											<div class="u-text">
												<h4><?= htmlspecialchars($displayName) ?></h4>
												<p class="text-muted">-</p>
											</div>
										</div>
									</li>
									<li>
										<div class="dropdown-divider"></div>
										<a class="dropdown-item" href="../logout.php">Logout</a>
									</li>
								</div>
							</ul>
						</li>
					</ul>
				</div>
			</nav>
			<!-- End Navbar -->
		</div>

		<!-- Sidebar -->
		<div class="sidebar sidebar-style-2">			
			<div class="sidebar-wrapper scrollbar scrollbar-inner">
				<div class="sidebar-content">
					<div class="user">
						<div class="avatar-sm float-left mr-2">
							<img src="../assets/img/profile.jpg" class="avatar-img rounded-circle">
						</div>
						<div class="info">
							<a data-toggle="collapse" href="#collapseExample" aria-expanded="true">
								<span>
                   <?= htmlspecialchars($displayName) ?>
                  <span class="caret"></span>
                </span>
							</a>
							<div class="clearfix"></div>

							<div class="collapse in" id="collapseExample">
								<ul class="nav">
									<li>
										<a href="../logout.php">
											<span class="link-collapse">Logout</span>
										</a>
									</li>
								</ul>
							</div>
						</div>
					</div>
					<ul class="nav nav-primary">
            <li class="nav-item active">
              <a data-toggle="collapse" href="index.php" class="collapsed" aria-expanded="false">
                <i class="fas fa-home"></i>
                <p>Dashboard</p>
              </a>
            </li>
            <li class="nav-section">
              <span class="sidebar-mini-icon"><i class="fa fa-ellipsis-h"></i></span>
              <h4 class="text-section">Cepu Server</h4>
            </li>

  <!-- OU Komputer -->
  <li class="nav-item">
    <a data-toggle="collapse" href="#cepuComputer">
      <i class="fas fa-layer-group"></i>
      <p>Cepu Computer</p>
      <span class="caret"></span>
    </a>
    <div class="collapse" id="cepuComputer">
      <ul class="nav nav-collapse">
        <li><a href="#" onclick="loadOU('USB Allowed')"><span class="sub-item">USB Allowed</span></a></li>
        <li><a href="#" onclick="loadOU('USB Restricted')"><span class="sub-item">USB Restricted</span></a></li>
      </ul>
    </div>
  </li>

  <!-- OU Users -->
  <li class="nav-item">
    <a data-toggle="collapse" href="#cepuGroup">
      <i class="fas fa-th-list"></i>
      <p>Cepu Group</p>
      <span class="caret"></span>
    </a>
    <div class="collapse" id="cepuGroup">
      <ul class="nav nav-collapse">
        <li><a href="#" onclick="loadOU('GSU')"><span class="sub-item">GSU</span></a></li>
        <li><a href="#" onclick="loadOU('PSU')"><span class="sub-item">PSU</span></a></li>
        <li><a href="#" onclick="loadOU('RU')"><span class="sub-item">RU</span></a></li>
        <li><a href="#" onclick="loadOU('UCF')"><span class="sub-item">UCF</span></a></li>
        <li><a href="#" onclick="loadOU('UCO')"><span class="sub-item">UCO</span></a></li>
        <li><a href="#" onclick="loadOU('Users')"><span class="sub-item">Users</span></a></li>
      </ul>
    </div>
  </li>


    <li class="nav-item">
    <a data-toggle="collapse" href="#emailgroup">
      <i class="fas fa-layer-group"></i>
      <p>Email</p>
      <span class="caret"></span>
    </a>
    <div class="collapse" id="emailgroup">
      <ul class="nav nav-collapse">
        <li><a href="#" onclick="loadOU('USB Allowed')"><span class="sub-item">cepu.co</span></a></li>
        <li><a href="#" onclick="loadOU('USB Restricted')"><span class="sub-item">cepu.com</span></a></li>
        <li><a href="#" onclick="loadOU('USB Allowed')"><span class="sub-item">cepu.net</span></a></li>
      </ul>
    </div>
  </li>
</ul>
</div>
</div>
</div>
		<!-- End Sidebar -->

		<div class="main-panel">
			<div class="content">
				<div class="panel-header bg-primary-gradient">
					<div class="page-inner py-5">
						<div class="d-flex align-items-left align-items-md-center flex-column flex-md-row">
							<div>
								<h2 class="text-white pb-2 fw-bold">Cepu Server</h2>
								<h5 class="text-white op-7 mb-2">Active Directory Management System</h5>
							</div>
							<div class="ml-md-auto py-2 py-md-0">
								
							</div>
						</div>
					</div>
				</div>
				<div class="page-inner mt--5">
					<div class="row mt--2">
						<div class="col-md-6">
							<div class="card full-height">
								<div class="card-body">
									<div class="card-title">Overall statistics</div>
									<div class="card-category">Daily information about statistics in system</div>
									<div class="d-flex flex-wrap justify-content-around pb-2 pt-4">
										<div class="px-2 pb-2 pb-md-0 text-center">
										<div id="circles-1"></div>
											<h6 class="fw-bold mt-3 mb-0" id="activeUsers">Active Users</h6>
										</div>
										<div class="px-2 pb-2 pb-md-0 text-center">
											<div id="circles-2"></div>
											<h6 class="fw-bold mt-3 mb-0 " id="activePC">Active PC</h6>
										</div>
										<div class="px-2 pb-2 pb-md-0 text-center">
											<div id="circles-3"></div>
											<h6 class="fw-bold mt-3 mb-0" id="disabledTotal">Disable Users & PC</h6>
										</div>
									</div>
								</div>
							</div>
						</div>
            <div class="col-md-6">
  <div class="card full-height">
    <div class="card-body">
      <div class="card-title" id="judulPertumbuhanUser">Pertumbuhan User Windows</div>
      <div class="row py-3">
        <div class="col-md-4 d-flex flex-column justify-content-around">
          <div>
            <h6 class="fw-bold text-uppercase text-success op-8">Penambahan User</h6>
            <h3 class="fw-bold">0</h3> 
          </div>
          <div>
            <h6 class="fw-bold text-uppercase text-danger op-8">Disabled User</h6>
            <h3 class="fw-bold">0</h3> 
          </div>
        </div>
        <div class="col-md-8">
          <div id="chart-container" style="height: 250px;">
            <canvas id="totalAddUsers"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
            <div class="col-md-12" id="ouUserSection" style="display: none;">
							<div class="card">
								<div class="card-header">
									<div class="d-flex align-items-center">
										<h4 class="card-title">OU: <span id="ouTitle">-</span></h4>
										<button class="btn btn-primary" data-toggle="modal" data-target="#modalAddUser">Tambah User</button>
									</div>
								</div>
								<div class="card-body">
									<!-- Modal Tambah User -->
<div id="modalAddUser" class="modal">
  <div class="modal-content">
    <h4>Tambah User Baru</h4>
    <form id="formAddUser">
      <input type="text" name="nama" placeholder="Nama Lengkap" required>
      <input type="text" name="username" placeholder="Username" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>

      <label><input type="checkbox" name="force_change"> Wajib ganti password saat login pertama</label>

      <label>Group Pengguna:</label><br>
      <div id="groupList">
        <!-- Group list akan di-generate dari JS -->
      </div>

      <button type="submit">Simpan</button>
    </form>
  </div>
</div>
									<div class="table-responsive">
                    <div class="modal fade" id="confirmToggleModal" tabindex="-1" role="dialog">
                      <div class="modal-dialog modal-sm" role="document">
                        <div class="modal-content text-center">
                          <div class="modal-header">
                            <h5 class="modal-title">Konfirmasi</h5>
                          </div>
                          <div class="modal-body">
                            <p id="confirmMessage">Yakin ingin mengubah status user?</p>
                          </div>
                          <div class="modal-footer justify-content-center">
                            <button id="confirmYes" class="btn btn-danger">Ya</button>
                            <button class="btn btn-secondary" data-dismiss="modal">Tidak</button>
                          </div>
                        </div>
                      </div>
                    </div>
										<table id="add-row" class="display table table-striped table-hover">
                    <thead>
                      <tr>
                        <th>No</th>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th>Terakhir Login</th>
                        <th style="width: 10%">Action</th>
                      </tr>
                    </thead>
                    <tbody id="dataAD"></tbody>
                  </table>
									</div>
								</div>
							</div>
						</div>
          </div>
        </div>
      </div>
        <div class="container-fluid">
        <footer class="footer">
            <nav class="pull-left">
              <ul class="nav">
                <li class="nav-item">
                    <a class="nav-link" href="#">KangCepu</a> 
                  </li>
                </ul>
              </nav>
            </div>
			    </footer>
  <!--Core File JS Offline-->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
	<script src="../assets/js/plugin/chart.js/chart.min.js"></script>
	<script src="../assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>
	<script src="../assets/js/plugin/chart-circle/circles.min.js"></script>
	<script src="../assets/js/plugin/datatables/datatables.min.js"></script>
	<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
	<script src="../assets/js/plugin/jqvmap/jquery.vmap.min.js"></script>
	<script src="../assets/js/plugin/jqvmap/maps/jquery.vmap.world.js"></script>
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>
	<script src="../assets/js/atlantis.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

	<script>
		Circles.create({
			id:'circles-1',
			radius:45,
			value:60,
			maxValue:100,
			width:7,
			text: 5,
			colors:['#f1f1f1', '#FF9E27'],
			duration:400,
			wrpClass:'circles-wrp',
			textClass:'circles-text',
			styleWrapper:true,
			styleText:true
		})

		Circles.create({
			id:'circles-2',
			radius:45,
			value:70,
			maxValue:100,
			width:7,
			text: 36,
			colors:['#f1f1f1', '#2BB930'],
			duration:400,
			wrpClass:'circles-wrp',
			textClass:'circles-text',
			styleWrapper:true,
			styleText:true
		})

		Circles.create({
			id:'circles-3',
			radius:45,
			value:40,
			maxValue:100,
			width:7,
			text: 12,
			colors:['#f1f1f1', '#F25961'],
			duration:400,
			wrpClass:'circles-wrp',
			textClass:'circles-text',
			styleWrapper:true,
			styleText:true
		})
    if(document.getElementById("totalIncomeChart ")){
		var totalIncomeChart = document.getElementById('totalIncomeChart').getContext('2d');

		var mytotalIncomeChart = new Chart(totalIncomeChart, {
			type: 'bar',
			data: {
				labels: ["S", "M", "T", "W", "T", "F", "S", "S", "M", "T"],
				datasets : [{
					label: "Total Income",
					backgroundColor: '#ff9e27',
					borderColor: 'rgb(23, 125, 255)',
					data: [6, 4, 9, 5, 4, 6, 4, 3, 8, 10],
				}],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				legend: {
					display: false,
				},
				scales: {
					yAxes: [{
						ticks: {
							display: false 
						},
						gridLines : {
							drawBorder: false,
							display : false
						}
					}],
					xAxes : [ {
						gridLines : {
							drawBorder: false,
							display : false
						}
					}]
				},
			}
		});
  }

		$('#lineChart').sparkline([105,103,123,100,95,105,115], {
			type: 'line',
			height: '70',
			width: '100%',
			lineWidth: '2',
			lineColor: '#ffa534',
			fillColor: 'rgba(255, 165, 52, .14)'
		});
	</script>


    <!--Backend JS-->
<script>
let table;

function loadOU(ou) {
  if (table) {
    table.destroy();
    $('#add-row').empty(); 
    $('#add-row').html(`
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Lengkap</th>
          <th>Username</th>
          <th>Email</th>
          <th>Status</th>
          <th>Dibuat</th>
          <th>Terakhir Login</th>
          <th style="width: 10%">Action</th>
        </tr>
      </thead>
    `);
  }

  table = $('#add-row').DataTable({
    ajax: {
      url: 'ajax/load_ad_users.php',
      type: 'POST',
      data: { ou: ou }
    },
    destroy: true
  });
  console.log($("#add-row").DataTable().rows().count() + "#1")
}

$(document).ready(function () {
  $('.ou-link').on('click', function (e) {
    e.preventDefault();
    const ou = $(this).data('ou');
    $('#ouTitle').text(ou);
    loadOU(ou);
      console.log($("#add-row").DataTable().rows().count() + "#2")
  });
});
</script>

<script>
function loadOU(ouName) {
  fetch(`ajax/load_ad_users.php?ou=${encodeURIComponent(ouName)}`)
    .then(response => response.text())
    .then(html => {
      document.getElementById('dataAD').innerHTML = html;
        console.log($("#add-row").DataTable().rows().count() + "#3")
    })
    .catch(error => {
      console.error("Gagal ambil data OU:", error);
      document.getElementById('dataAD').innerHTML = `<tr><td colspan="6">Gagal load data OU ${ouName}</td></tr>`;
    });
}
</script>

<script>
  function loadOU(label) {
  document.getElementById("ouUserSection").style.display = "block";
  document.getElementById("ouTitle").textContent = label;
  document.getElementById("dataAD").innerHTML = '<tr><td colspan="8">Loading...</td></tr>';

  fetch(`ajax/load_ad_users.php?ou=${label}`)
    .then(response => response.text())
    .then(data => {
      console.log(data)
      $("#add-row").DataTable().destroy()
      document.getElementById("dataAD").innerHTML = data;
      $("#add-row").DataTable()
    })
    .catch(error => {
      console.error("Error loading users:", error);
      document.getElementById("dataAD").innerHTML = '<tr><td colspan="8">Gagal memuat data</td></tr>';
    });
}
  </script>


<script>
document.addEventListener("DOMContentLoaded", function () {
  fetch("ajax/dashboard_summary.php")
    .then(res => res.json())
    .then(data => {
      const totalDisabled = data.user_disabled + data.pc_disabled;

      Circles.create({
        id: 'circles-1',
        radius: 45,
        value: data.user_active,
        maxValue: 100,
        width: 7,
        text: data.user_active,
        colors: ['#f1f1f1', '#FF9E27'],
        duration: 400,
        wrpClass: 'circles-wrp',
        textClass: 'circles-text',
        styleWrapper: true,
        styleText: true
      });

      Circles.create({
        id: 'circles-2',
        radius: 45,
        value: data.pc_active,
        maxValue: 100,
        width: 7,
        text: data.pc_active,
        colors: ['#f1f1f1', '#2BB930'],
        duration: 400,
        wrpClass: 'circles-wrp',
        textClass: 'circles-text',
        styleWrapper: true,
        styleText: true
      });

      Circles.create({
        id: 'circles-3',
        radius: 45,
        value: totalDisabled,
        maxValue: 100,
        width: 7,
        text: totalDisabled,
        colors: ['#f1f1f1', '#F25961'],
        duration: 400,
        wrpClass: 'circles-wrp',
        textClass: 'circles-text',
        styleWrapper: true,
        styleText: true
      });

      document.getElementById("activeUsers").innerText = "Active Users";
      document.getElementById("activePC").innerText = "Active PC";
      document.getElementById("disabledTotal").innerText = "Disabled Users & PC";
    });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    fetch("ajax/load_growth_user.php")
        .then(response => response.json())
        .then(data => {
            const bulan = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];

            if(document.getElementById("totalIncomeChart ")){
            const ctx = document.getElementById("totalIncomeChart").getContext("2d");
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: bulan,
                    datasets: [
                        {
                            label: 'User Aktif',
                            backgroundColor: '#47C363',
                            data: data.enabled
                        },
                        {
                            label: 'User Disabled',
                            backgroundColor: '#FC544B',
                            data: data.disabled
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: 'Pertumbuhan User Tahun ' + new Date().getFullYear()
                        }
                    }
                }
            });
          }
            const totalAktif = data.enabled.reduce((a, b) => a + b, 0);
            const totalDisable = data.disabled.reduce((a, b) => a + b, 0);
            document.querySelector(".text-success + h3").textContent = totalAktif;
            document.querySelector(".text-danger + h3").textContent = totalDisable;
        })
        .catch(error => {
            console.error("Gagal load data pertumbuhan user:", error);
        });
});
</script>

<script>
function getCurrentMonthYear() {
  const bulan = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
  const now = new Date();
  return bulan[now.getMonth()] + " " + now.getFullYear();
}
document.addEventListener("DOMContentLoaded", function () {
    fetch("ajax/load_growth_user.php")
        .then(response => response.json())
        .then(data => {
            const bulanPendek = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];

            const ctx = document.getElementById("totalAddUsers").getContext("2d");
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: bulanPendek,
                    datasets: [
                        {
                            label: 'User Aktif',
                            backgroundColor: '#47C363',
                            data: data.enabled
                        },
                        {
                            label: 'User Disabled',
                            backgroundColor: '#FC544B',
                            data: data.disabled
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: 'Pertumbuhan User Windows ' + getCurrentMonthYear()
                        }
                    }
                }
            });
            const totalAktif = data.enabled.reduce((a, b) => a + b, 0);
            const totalDisable = data.disabled.reduce((a, b) => a + b, 0);
            document.querySelector(".text-success + h3").textContent = totalAktif;
            document.querySelector(".text-danger + h3").textContent = totalDisable;
            const titleElement = document.querySelector(".card-title");
            if (titleElement) {
                titleElement.textContent = "Pertumbuhan User Windows " + getCurrentMonthYear();
            }
        })
        .catch(error => {
            console.error("Gagal load data pertumbuhan user:", error);
        });
});
</script>

<script>
$(document).ready(function() {
  console.log($("#add-row").DataTable().rows().count())
});
</script>

<script>
  let selectedUser = '';
let selectedStatus = '';
let currentOU = '';

$(document).on('click', '.status-toggle', function () {
  selectedUser = $(this).data('username');
  selectedStatus = $(this).data('status');
  currentOU = $('#ouTitle').text();

  const targetStatus = selectedStatus === 'Active' ? 'menonaktifkan' : 'mengaktifkan';
  $('#confirmMessage').text(`Apakah Anda yakin ingin ${targetStatus} user ${selectedUser}?`);
  $('#confirmToggleModal').modal('show');
});

$('#confirmYes').on('click', function () {
  const badge = $(`.status-toggle[data-username="${selectedUser}"]`);
  badge.html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`);

  $.ajax({
    url: 'ajax/toggle_status.php',
    method: 'POST',
    data: { username: selectedUser, currentStatus: selectedStatus },
    success: function (res) {
      setTimeout(() => {
        $('#confirmToggleModal').modal('hide');
        const newStatus = selectedStatus === 'Active' ? 'Disabled' : 'Active';
        const newClass = selectedStatus === 'Active' ? 'badge-danger' : 'badge-success';
        const oldClass = selectedStatus === 'Active' ? 'badge-success' : 'badge-danger';
        badge.fadeOut(150, function () {
          $(this)
            .removeClass(oldClass)
            .addClass(newClass)
            .text(newStatus)
            .data('status', newStatus)
            .fadeIn(150);
        });
      }, 500);
    },
    error: function () {
      badge.html(selectedStatus);
      alert('Gagal mengubah status user.');
      $('#confirmToggleModal').modal('hide');
    }
  });
});
</script>

<script>
  $('#formAddUser').on('submit', function(e) {
  e.preventDefault();

  $.ajax({
    url: 'ajax/add_user.php',
    method: 'POST',
    data: $(this).serialize(),
    success: function(response) {
      alert('User berhasil ditambahkan');
      $('#modalAddUser').modal('hide');
      $('#formAddUser')[0].reset();
      $('.ou-link.active').trigger('click');
    },
    error: function() {
      alert('Gagal tambah user!');
    }
  });
});
</script>

<script>
const groupData = ["GSU", "PSU", "RU", "UCF", "UCO"];

$(document).ready(function () {
  groupData.forEach(grp => {
    $('#groupList').append(`
      <label>
        <input type="checkbox" name="groups[]" value="${grp}"> ${grp}
      </label><br>
    `);
  });

  $('#formAddUser').on('submit', function (e) {
    e.preventDefault();

    const formData = $(this).serialize(); 

    $.post('ajax/add_user.php', formData, function (res) {
      if (res.status) {
        alert('User berhasil ditambahkan');
        $('#modalAddUser').hide(); 
      } else {
        alert('Gagal tambah user: ' + res.message);
      }
    }, 'json');
  });
});
</script>
</body>
</html>