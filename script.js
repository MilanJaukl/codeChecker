document.addEventListener("DOMContentLoaded", function () {
  const repositories = document.getElementById("repositories");
  const branches = document.getElementById("branches");
  const commits = document.getElementById("commits");
  const filesList = document.getElementById("filesList");
  const filesListContainer = document.getElementById("fileListContainer");
  const codeWrapper = document.getElementById("codeWrapper");
  const issuesWrapper = document.getElementById("issuesWrapper");

  let actualRepository = "";
  let actualBranch = "";
  let actualCommit = "";
  let codeReviews = [];
  let data = [];
  let files = [];

  getRepositories();
  initTabComponent();

  repositories.addEventListener("change", function (event) {
    const repository = event.target.value;
    actualRepository = repository;
    getBranches(actualRepository);
  });

  branches.addEventListener("change", function (event) {
    const branch = event.target.value;
    actualBranch = branch;
    getCommits(actualRepository, actualBranch);
  });

  // create delegated event listeners for commit_items
  commits.addEventListener("click", function (event) {
    if (event.target.classList.contains("commit_item")) {
      const commitId = event.target.getAttribute("data-id");
      actualCommit = commitId;
      alert("kliknul");
      getCodeReviews(actualRepository, actualBranch, actualCommit);
    }
  });

  // create delegated event listeners for file_items
  filesList.addEventListener("click", function (event) {
    if (event.target.classList.contains("file_item")) {
      const fileIndex = event.target.getAttribute("data-id");
      showFileReview(fileIndex);
    }
  });

  function getRepositories() {
    axios
      .get("http://localhost:8001/repos")
      .then((response) => {
        response.data.repositories.forEach((repo) => {
          const option = document.createElement("option");
          option.value = repo;
          option.text = repo;
          repositories.appendChild(option);
        });
      })
      .catch((error) => {
        console.error("Error fetching repositories:", error);
      });
  }
});

function getBranches(repository) {
  axios
    .get(`http://localhost:8001/branches?repository=${repository}`)
    .then((response) => {
      response.data.branches.forEach((branch) => {
        const option = document.createElement("option");
        option.value = branch;
        option.text = branch;
        branches.appendChild(option);
      });
    })
    .catch((error) => {
      console.error("Error fetching branches:", error);
    });
}

function getCommits(actualRepository, actualBranch) {
  axios
    .get(
      `http://localhost:8001/commits?repository=${actualRepository}&branch=${actualBranch}`
    )
    .then((response) => {
      response.data.commits.forEach((commit) => {
        const li = document.createElement("li");
        const container = document.createElement("div");
        const commitMessage = document.createElement("p");
        commitMessage.textContent = commit.message;
        const commitId = document.createElement("p");
        commitId.textContent = commit.hash.substring(0, 7);
        container.appendChild(commitMessage);
        container.appendChild(commitId);
        container.classList.add(
          "border",
          "border-gray-300",
          "p-2",
          "mb-2",
          "hover:bg-blue-300",
          "hover:text-white",
          "cursor-pointer",
          "commit_item"
        );
        container.setAttribute("data-id", commit.hash);

        // togle the bg on click
        container.addEventListener("click", function () {
          container.classList.toggle("bg-blue-400");
          container.classList.toggle("text-white");
        });
        li.appendChild(container);
        commits.appendChild(li);
      });
    })
    .catch((error) => {
      console.error("Error fetching commits:", error);
    });
}

function getCodeReviews(actualRepository, actualBranch, actualCommit) {
  axios
    .get(
      `http://localhost:8001/code_reviews?repository=${actualRepository}&branch=${actualBranch}&commit=${actualCommit}`
    )
    .then((response) => {
      codeReviews = response.data.reviews;
      console.log(codeReviews);
      prepareData(codeReviews);
    })
    .catch((error) => {
      console.error("Error fetching code reviews:", error);
    });
}

function prepareData(codeReviews) {
  // data = codeReviews.map((review) => {
  //   return {
  //     id: review.id,
  //     reviewer: review.reviewer,
  //     status: review.status,
  //     comment: review.comment,
  //   };
  // });

  files = codeReviews.map((review) => {
    return review.file_name;
  });
  console.log(files);
  showFileList(files);
}

function showFileList(files) {
  if (files.length === 0) {
    filesListContainer.classList.add("hidden");
    return;
  } else {
    files.forEach((file, index) => {
      const container = document.createElement("div");
      container.textContent = file;
      container.classList.add("cursor-pointer", "file_item");
      container.setAttribute("data-id", index);

      filesList.appendChild(container);
    });

    filesListContainer.classList.remove("hidden");
  }
}

function showFileReview(fileIndex) {
  // clear the codeWrapper and issuesWrapper
  codeWrapper = document.querySelector(".tab.active .codeWrapper");
  issuesWrapper = document.querySelector(".tab.active .issuesWrapper");
  if (!codeWrapper || !issuesWrapper) {
    return;
  }
  codeWrapper.innerHTML = "";
  issuesWrapper.textContent = "";

  const file = codeReviews[fileIndex];
  const pre = document.createElement("pre");
  pre.style.fontSize = "0.7rem";
  const code = document.createElement("code");
  code.classList.add("language-php");
  const formattedCode = file.code.replace(/</g, "&lt;").replace(/>/g, "&gt;");

  code.innerHTML = formattedCode;
  pre.appendChild(code);
  codeWrapper.appendChild(pre);
  issuesWrapper.textContent = file.review;
  console.log(file);
  Prism.highlightAll();
}

function initTabComponent() {
  const tabs = Array.from(document.getElementsByClassName("tab"));
  const tabSwitches = Array.from(document.getElementsByClassName("tab_switch"));

  tabSwitches.forEach((tabSwitch) => {
    tabSwitch.addEventListener("click", function () {
      tabSwitches.forEach((ts) => ts.classList.remove("active"));
      tabSwitch.classList.add("active");

      const target = tabSwitch.getAttribute("data-tab-id");
      tabs.forEach((tab) => {
        if (tab.id === target) {
          tab.classList.remove("hidden");
          tab.classList.add("active");
        } else {
          tab.classList.add("hidden");
          tab.classList.remove("active");
        }
      });
    });
  });
}
